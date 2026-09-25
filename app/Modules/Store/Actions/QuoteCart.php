<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Data\PricingContext;
use App\Modules\Store\Enums\PricingChannel;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Services\InclusiveVatCalculator;
use App\Modules\Store\Services\PricingContextResolver;
use App\Modules\Store\Services\StorePricing;
use App\Modules\Store\Settings\StoreSettings;
use Illuminate\Validation\ValidationException;

class QuoteCart
{
    public function __construct(
        private StoreSettings $settings,
        private InclusiveVatCalculator $vatCalculator,
        private StorePricing $pricing,
        private PricingContextResolver $contexts,
    ) {}

    /**
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     subtotal_baisa: int,
     *     vat_baisa: int,
     *     total_baisa: int,
     *     regular_total_baisa: int,
     *     discount_baisa: int,
     *     pricing_tier: string,
     *     currency: string,
     *     reservation_quantities: array<int, int>
     * }
     */
    public function execute(Cart $cart, bool $lockOptions = false, ?PricingContext $context = null): array
    {
        $context ??= $this->contexts->forCart($cart);
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

        // Product option prices are final, VAT-inclusive amounts.
        $subtotal = 0;
        $vat = 0;
        $total = 0;
        $regularTotal = 0;
        $discount = 0;
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

            $optionPrice = $this->pricing->forOption($option, $context);
            $lineTotal = $optionPrice->effectivePriceBaisa * $item->quantity;
            $lineRegularTotal = $optionPrice->regularPriceBaisa * $item->quantity;
            $lineDiscount = $optionPrice->unitDiscountBaisa * $item->quantity;
            $lineAmounts = $this->vatCalculator->calculate($lineTotal, $this->settings->vat_rate_percentage);
            $lineSubtotal = $lineAmounts['net_baisa'];
            $lineVat = $lineAmounts['vat_baisa'];
            $subtotal += $lineSubtotal;
            $vat += $lineVat;
            $total += $lineTotal;
            $regularTotal += $lineRegularTotal;
            $discount += $lineDiscount;
            $snapshots[] = [
                'product_option_id' => $option->id,
                'product_name' => $option->product->name,
                'option_name' => $option->name,
                'sku' => $option->sku,
                'currency' => $option->currency,
                'unit_price_baisa' => $optionPrice->effectivePriceBaisa,
                'regular_unit_price_baisa' => $optionPrice->regularPriceBaisa,
                'unit_discount_baisa' => $optionPrice->unitDiscountBaisa,
                'quantity' => $item->quantity,
                'vat_baisa' => $lineVat,
                'line_subtotal_baisa' => $lineSubtotal,
                'line_total_baisa' => $lineTotal,
                'line_discount_baisa' => $lineDiscount,
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
            'total_baisa' => $total,
            'regular_total_baisa' => $regularTotal,
            'discount_baisa' => $discount,
            'pricing_tier' => $context->tier->value,
            'currency' => 'OMR',
            'reservation_quantities' => $reservationQuantities,
        ];
    }

    /**
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     subtotal_baisa: int,
     *     vat_baisa: int,
     *     total_baisa: int,
     *     regular_total_baisa: int,
     *     discount_baisa: int,
     *     pricing_tier: string,
     *     currency: string,
     *     reservation_quantities: array<int, int>
     * }
     */
    public function executeOrEmpty(
        Cart $cart,
        PricingChannel $channel = PricingChannel::Storefront,
    ): array {
        $context = $this->contexts->forCart($cart, $channel);

        return $cart->items()->exists()
            ? $this->execute($cart, context: $context)
            : $this->emptyQuote($context);
    }

    /**
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     subtotal_baisa: int,
     *     vat_baisa: int,
     *     total_baisa: int,
     *     regular_total_baisa: int,
     *     discount_baisa: int,
     *     pricing_tier: string,
     *     currency: string,
     *     reservation_quantities: array<int, int>
     * }
     */
    public function emptyQuote(PricingContext $context): array
    {
        return [
            'items' => [],
            'subtotal_baisa' => 0,
            'vat_baisa' => 0,
            'total_baisa' => 0,
            'regular_total_baisa' => 0,
            'discount_baisa' => 0,
            'pricing_tier' => $context->tier->value,
            'currency' => 'OMR',
            'reservation_quantities' => [],
        ];
    }
}
