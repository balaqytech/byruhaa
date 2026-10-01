<?php

namespace App\Modules\Store\Actions;

use App\Modules\Identity\Contracts\MinorProfilePurchasing;
use App\Modules\Identity\Models\Customer;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateAdminOrder
{
    public function __construct(
        private AddCartItem $addCartItem,
        private CreateOrder $createOrder,
        private ConfirmWalletOrder $confirmWalletOrder,
        private MinorProfilePurchasing $minorProfiles,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, string $idempotencyKey): Order
    {
        $validated = Validator::make($data, [
            'customer_id' => ['required', 'integer', Rule::exists((new Customer)->getTable(), 'id')],
            'minor_profile_id' => ['nullable', 'integer'],
            'payment_method' => ['required', Rule::in(['thawani', 'wallet'])],
            'pickup_type' => ['required', Rule::in(['immediate', 'scheduled'])],
            'pickup_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_option_id' => ['required', 'integer', 'distinct', Rule::exists((new ProductOption)->getTable(), 'id')],
            'items.*.quantity' => ['required', 'integer', 'between:1,99'],
        ])->validate();

        $customerId = (int) $validated['customer_id'];
        $minorProfileId = filled($validated['minor_profile_id'] ?? null) ? (int) $validated['minor_profile_id'] : null;

        if ($validated['payment_method'] === 'wallet' && $minorProfileId === null) {
            throw ValidationException::withMessages(['payment_method' => __('admin.store.admin_order.wallet_requires_minor')]);
        }

        $customer = Customer::query()->findOrFail($customerId);
        $minor = $minorProfileId === null ? null : $this->minorProfiles->forGuardian($minorProfileId, $customerId);

        return DB::transaction(function () use ($validated, $idempotencyKey, $customer, $customerId, $minor, $minorProfileId): Order {
            $cart = Cart::query()->create([
                'customer_id' => $customerId,
                'minor_profile_id' => $minorProfileId,
            ]);

            foreach ($validated['items'] as $item) {
                $this->addCartItem->execute(
                    $cart,
                    ProductOption::query()->findOrFail((int) $item['product_option_id']),
                    (int) $item['quantity'],
                    checkInventory: true,
                );
            }

            $order = $this->createOrder->execute($cart, [
                'idempotency_key' => $idempotencyKey,
                'customer_id' => $customerId,
                'minor_profile_id' => $minorProfileId,
                'payment_method' => $validated['payment_method'],
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone_number,
                'customer_email' => $customer->email,
                'recipient_name' => $minor?->name,
                'note' => $validated['note'] ?? null,
                'pickup_type' => $validated['pickup_type'],
                'pickup_at' => $validated['pickup_at'] ?? null,
            ]);

            if ($validated['payment_method'] === 'wallet') {
                $order = $this->confirmWalletOrder->execute($order, $customerId, $minorProfileId);
            }

            $cart->delete();

            return $order;
        });
    }
}
