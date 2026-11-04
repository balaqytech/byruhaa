<?php

namespace App\Modules\Pos\Actions;

use App\Modules\Identity\Contracts\PosPurchasing;
use App\Modules\Identity\Data\MinorProfilePurchaseData;
use App\Modules\Identity\Models\User;
use App\Modules\Store\Contracts\PosOrderCheckout;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreatePosOrder
{
    public function __construct(private PosPurchasing $credentials, private PosOrderCheckout $checkout) {}

    /** @param array<int, array{product_option_id: int, quantity: int, note?: string|null}> $items */
    public function execute(User $cashier, string $token, array $items, int $reviewedTotalBaisa, string $idempotencyKey, int $reviewedProfileId): Order
    {
        abort_unless($cashier->isPanelAdministrator() || $cashier->can('Sell:Pos'), 403);

        $items = Validator::make(['items' => $items], [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_option_id' => ['required', 'integer', 'distinct', Rule::exists((new ProductOption)->getTable(), 'id')],
            'items.*.quantity' => ['required', 'integer', 'between:1,99'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
        ])->validate()['items'];

        if ($reviewedTotalBaisa < 1 || trim($idempotencyKey) === '' || strlen($idempotencyKey) > 255) {
            throw ValidationException::withMessages(['total' => 'راجع إجمالي الطلب.']);
        }

        $requestHash = hash('sha256', json_encode([$cashier->id, hash('sha256', $token), $items, $reviewedTotalBaisa, $reviewedProfileId], JSON_THROW_ON_ERROR));

        return $this->credentials->withVerifiedCard($token, function (MinorProfilePurchaseData $profile) use ($cashier, $items, $reviewedTotalBaisa, $idempotencyKey, $requestHash, $reviewedProfileId): Order {
            if (! $profile->walletSpendingEnabled) {
                throw ValidationException::withMessages(['wallet' => 'الدفع من المحفظة متوقف لهذا القائد.']);
            }

            if ($profile->profileId !== $reviewedProfileId) {
                throw ValidationException::withMessages(['card' => 'تغيرت البطاقة بعد مراجعة الإجمالي. امسح البطاقة مجددًا.']);
            }

            $existing = Order::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();

            if ($existing instanceof Order) {
                if ($existing->pos_cashier_user_id !== $cashier->id || $existing->pos_request_hash !== $requestHash) {
                    throw ValidationException::withMessages(['order' => 'مفتاح العملية مستخدم لطلب آخر.']);
                }

                return $existing;
            }

            return $this->checkout->execute([
                'customer_id' => $profile->guardianId,
                'minor_profile_id' => $profile->profileId,
                'payment_method' => 'wallet',
                'pickup_type' => 'immediate',
                'items' => $items,
            ], $idempotencyKey, $cashier->id, $reviewedTotalBaisa, $requestHash);
        });
    }
}
