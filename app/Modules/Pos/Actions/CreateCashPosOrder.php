<?php

namespace App\Modules\Pos\Actions;

use App\Modules\Identity\Contracts\PosPurchasing;
use App\Modules\Identity\Data\MinorProfilePurchaseData;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\PhoneNumberNormalizer;
use App\Modules\Store\Contracts\PosOrderCheckout;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateCashPosOrder
{
    public function __construct(
        private PosPurchasing $credentials,
        private PosOrderCheckout $checkout,
        private PhoneNumberNormalizer $phoneNumbers,
    ) {}

    /** @param array<string, mixed> $sale */
    public function execute(User $cashier, array $sale): Order
    {
        abort_unless($cashier->isPanelAdministrator() || $cashier->can('Sell:Pos'), 403);

        $validated = Validator::make($sale, [
            'buyer_type' => ['required', Rule::in(['guest', 'minor'])],
            'token' => ['nullable', 'string'],
            'reviewed_profile_id' => ['nullable', 'integer'],
            'guest_name' => ['nullable', 'string', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:32'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_option_id' => ['required', 'integer', 'distinct', Rule::exists((new ProductOption)->getTable(), 'id')],
            'items.*.quantity' => ['required', 'integer', 'between:1,99'],
            'reviewed_total_baisa' => ['required', 'integer', 'min:1'],
            'cash_received_baisa' => ['required', 'integer', 'min:1', 'max:999999999'],
            'idempotency_key' => ['required', 'string', 'max:255'],
        ])->validate();

        $buyerType = $validated['buyer_type'];
        $token = trim((string) ($validated['token'] ?? ''));
        $reviewedProfileId = $validated['reviewed_profile_id'] ?? null;

        if ($buyerType === 'minor' && ($token === '' || $reviewedProfileId === null)) {
            throw ValidationException::withMessages(['card' => 'امسح بطاقة القائد قبل الدفع.']);
        }

        if ($buyerType === 'guest' && ($token !== '' || $reviewedProfileId !== null)) {
            throw ValidationException::withMessages(['card' => 'اختر طلب القائد للشراء ببطاقته.']);
        }

        if ($validated['cash_received_baisa'] < $validated['reviewed_total_baisa']) {
            throw ValidationException::withMessages(['cashReceived' => 'المبلغ المستلم أقل من إجمالي الطلب.']);
        }

        $guestName = trim((string) ($validated['guest_name'] ?? ''));
        $guestPhone = $this->phoneNumbers->normalize(trim((string) ($validated['guest_phone'] ?? '')));
        $requestHash = hash('sha256', json_encode([
            $cashier->id,
            $buyerType,
            $buyerType === 'minor' ? hash('sha256', $token) : null,
            $reviewedProfileId,
            $guestName,
            $guestPhone,
            $validated['items'],
            $validated['reviewed_total_baisa'],
            $validated['cash_received_baisa'],
        ], JSON_THROW_ON_ERROR));

        $create = function (?MinorProfilePurchaseData $profile) use ($cashier, $validated, $reviewedProfileId, $guestName, $guestPhone, $requestHash): Order {
            if ($profile !== null && $profile->profileId !== $reviewedProfileId) {
                throw ValidationException::withMessages(['card' => 'تغيرت البطاقة بعد مراجعة الإجمالي. امسح البطاقة مجددًا.']);
            }

            $existing = Order::query()->where('idempotency_key', $validated['idempotency_key'])->lockForUpdate()->first();

            if ($existing instanceof Order) {
                if ($existing->payment_method !== 'cash'
                    || $existing->pos_cashier_user_id !== $cashier->id
                    || $existing->pos_request_hash !== $requestHash) {
                    throw ValidationException::withMessages(['order' => 'مفتاح العملية مستخدم لطلب آخر.']);
                }

                return $existing;
            }

            $data = $profile === null
                ? [
                    'customer_name' => $guestName !== '' ? $guestName : 'ضيف نقطة البيع',
                    'customer_phone' => $guestPhone,
                ]
                : [
                    'customer_id' => $profile->guardianId,
                    'minor_profile_id' => $profile->profileId,
                ];

            return $this->checkout->executeCashPos([
                ...$data,
                'pickup_type' => 'immediate',
                'items' => $validated['items'],
            ], $validated['idempotency_key'], $cashier->id, $validated['reviewed_total_baisa'], $requestHash, $validated['cash_received_baisa'], $profile === null);
        };

        if ($buyerType === 'minor') {
            return $this->credentials->withVerifiedCard($token, $create);
        }

        return DB::transaction(fn (): Order => $create(null));
    }
}
