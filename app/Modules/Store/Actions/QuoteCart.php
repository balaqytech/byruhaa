<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Settings\StoreSettings;
use Illuminate\Validation\ValidationException;

class QuoteCart
{
    public function __construct(private StoreSettings $settings) {}

    /**
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     subtotal_baisa: int,
     *     vat_baisa: int,
     *     total_baisa: int,
     *     currency: string,
     *     reservation_quantities: array<int, int>
     * }
     */
    public function execute(Cart $cart, bool $lockOptions = false): array
    {
        $items = $cart->items()
            ->with('productOption.product.category')
            ->orderBy('product_option_id')
            ->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'The cart is empty.']);
        }

        $optionIds = $items->pluck('product_option_id')->unique()->sort()->values();
        $optionsQuery = ProductOption::query()
            ->with([
                'product.category',
                'reservationItems' => fn ($reservationQuery) => $reservationQuery
                    ->whereHas('reservation', fn ($query) => $query
                        ->where('status', 'pending')
                        ->where('expires_at', '>', now())),
            ])
            ->whereIn('id', $optionIds)
            ->orderBy('id');
        $options = ($lockOptions ? $optionsQuery->lockForUpdate() : $optionsQuery)->get()->keyBy('id');

        $subtotal = 0;
        $vat = 0;
        $snapshots = [];
        $reservationQuantities = [];

        foreach ($items as $item) {
            $option = $options->get($item->product_option_id);
            if (! $option instanceof ProductOption) {
                throw ValidationException::withMessages(['cart' => 'A product in this cart is no longer available.']);
            }
            AddCartItem::ensurePurchasable($option);

            if ($option->currency !== 'OMR') {
                throw ValidationException::withMessages(['currency' => 'Only OMR products can be ordered.']);
            }

            if ($option->tracks_inventory && ($option->availableQuantity() ?? 0) < $item->quantity) {
                throw ValidationException::withMessages([
                    'cart' => "الكمية المطلوبة من {$option->product->name} غير متاحة حاليًا.",
                ]);
            }

            $lineSubtotal = $option->price_baisa * $item->quantity;
            $lineVat = intdiv($lineSubtotal * $this->settings->vat_rate_percentage, 100);
            $subtotal += $lineSubtotal;
            $vat += $lineVat;
            $snapshots[] = [
                'product_option_id' => $option->id,
                'product_name' => $option->product->name,
                'option_name' => $option->name,
                'sku' => $option->sku,
                'currency' => $option->currency,
                'unit_price_baisa' => $option->price_baisa,
                'quantity' => $item->quantity,
                'vat_baisa' => $lineVat,
                'line_subtotal_baisa' => $lineSubtotal,
                'line_total_baisa' => $lineSubtotal + $lineVat,
                'note' => $item->note,
            ];

            if ($option->tracks_inventory) {
                $reservationQuantities[$option->id] = ($reservationQuantities[$option->id] ?? 0) + $item->quantity;
            }
        }

        return [
            'items' => $snapshots,
            'subtotal_baisa' => $subtotal,
            'vat_baisa' => $vat,
            'total_baisa' => $subtotal + $vat,
            'currency' => 'OMR',
            'reservation_quantities' => $reservationQuantities,
        ];
    }
}
