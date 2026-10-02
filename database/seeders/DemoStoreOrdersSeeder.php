<?php

namespace Database\Seeders;

use App\Modules\Finance\Models\Wallet;
use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Store\Enums\ProductStatus;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\States\Order\Completed;
use App\Modules\Store\States\Order\Confirmed;
use App\Modules\Store\States\Order\PendingPayment;
use App\Modules\Store\States\Order\Preparing;
use App\Modules\Store\States\Order\ReadyForPickup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DemoStoreOrdersSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo orders may only be seeded in local or testing environments.');
        }

        DB::transaction(function (): void {
            $options = ProductOption::query()
                ->with('product')
                ->where('is_available', true)
                ->whereHas('product', fn ($query) => $query->where('status', ProductStatus::Active))
                ->orderBy('id')
                ->limit(8)
                ->get();

            if ($options->count() < 8) {
                throw new RuntimeException('Seed the store catalogue before creating demo orders.');
            }

            $customers = collect([1, 2])->map(function (int $number): Customer {
                return Customer::query()->firstOrCreate(
                    ['email' => "demo-customer-{$number}@example.test"],
                    [
                        'name' => "عميل تجريبي {$number}",
                        'phone_number' => "+9680000000{$number}",
                        'civil_id' => "0000000{$number}",
                        'address' => 'عنوان تجريبي',
                        'wilaya' => 'مسقط',
                        'area' => 'منطقة تجريبية',
                        'password' => Str::random(64),
                    ],
                );
            });

            $minors = $customers->map(function (Customer $customer, int $index): MinorProfile {
                $number = $index + 1;
                $familyMember = FamilyMember::query()->firstOrCreate(
                    ['customer_id' => $customer->getKey(), 'name' => "قاصر تجريبي {$number}"],
                    ['birth_date' => now()->subYears(13)->toDateString(), 'relationship_to_customer' => 'child'],
                );

                $minor = MinorProfile::query()->firstOrCreate(
                    ['family_member_id' => $familyMember->getKey()],
                    [
                        'member_code' => "DM00{$number}",
                        'password' => Str::random(64),
                        'status' => MinorProfileStatus::Active,
                        'wallet_spending_enabled' => true,
                        'activated_at' => now(),
                    ],
                );

                Wallet::query()->firstOrCreate(['minor_profile_id' => $minor->getKey()]);

                return $minor;
            });

            foreach ($options as $index => $option) {
                $isMinorOrder = $index >= 4;
                $customer = $customers[$index % 2];
                $minor = $isMinorOrder ? $minors[$index % 2] : null;
                $status = [
                    PendingPayment::$name,
                    Confirmed::$name,
                    Preparing::$name,
                    Completed::$name,
                    Confirmed::$name,
                    Preparing::$name,
                    ReadyForPickup::$name,
                    Completed::$name,
                ][$index];
                $unitPrice = $isMinorOrder
                    ? ($option->member_price_baisa ?? $option->price_baisa)
                    : $option->price_baisa;
                $quantity = ($index % 3) + 1;
                $subtotal = $unitPrice * $quantity;
                $vat = (int) round($subtotal * 0.05);
                $reference = sprintf('DEMO-ORD-%03d', $index + 1);

                if (Order::query()->where('idempotency_key', $reference)->exists()) {
                    continue;
                }

                $order = Order::query()->create([
                    'reference' => $reference,
                    'payment_token' => (string) Str::uuid(),
                    'idempotency_key' => $reference,
                    'customer_id' => $customer->getKey(),
                    'minor_profile_id' => $minor?->getKey(),
                    'status' => $status,
                    'payment_method' => $isMinorOrder ? 'wallet' : 'thawani',
                    'customer_name' => $minor?->familyMember->name ?? $customer->name,
                    'customer_phone' => $customer->phone_number,
                    'customer_email' => $customer->email,
                    'note' => 'طلب تجريبي للعرض فقط؛ لا توجد معاملة دفع أو خصم من المحفظة.',
                    'subtotal_baisa' => $subtotal,
                    'vat_baisa' => $vat,
                    'total_baisa' => $subtotal + $vat,
                    'regular_total_baisa' => ($option->price_baisa * $quantity) + $vat,
                    'discount_baisa' => ($option->price_baisa - $unitPrice) * $quantity,
                    'pricing_tier' => $isMinorOrder ? 'member' : 'standard',
                    'paid_at' => $status === PendingPayment::$name ? null : now(),
                    'payment_reference' => $status === PendingPayment::$name ? null : "DEMO-PAY-{$reference}",
                ]);

                $order->items()->create([
                    'product_option_id' => $option->getKey(),
                    'product_name' => $option->product->name,
                    'option_name' => $option->name,
                    'sku' => $option->sku,
                    'unit_price_baisa' => $unitPrice,
                    'regular_unit_price_baisa' => $option->price_baisa,
                    'unit_discount_baisa' => $option->price_baisa - $unitPrice,
                    'quantity' => $quantity,
                    'vat_baisa' => $vat,
                    'line_subtotal_baisa' => $subtotal,
                    'line_total_baisa' => $subtotal + $vat,
                    'line_discount_baisa' => ($option->price_baisa - $unitPrice) * $quantity,
                ]);

                $order->statusHistory()->create([
                    'from_status' => null,
                    'to_status' => $status,
                    'note' => 'بيانات تجريبية للعرض فقط',
                ]);
            }
        });
    }
}
