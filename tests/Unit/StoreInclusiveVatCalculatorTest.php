<?php

use App\Modules\Store\Services\InclusiveVatCalculator;

test('inclusive VAT extracts five percent from a final price using integer rounding', function (): void {
    expect(app(InclusiveVatCalculator::class)->calculate(2000, 5))
        ->toBe([
            'net_baisa' => 1905,
            'vat_baisa' => 95,
            'gross_baisa' => 2000,
        ]);
});

test('inclusive VAT returns the final price unchanged when the rate is zero', function (): void {
    expect(app(InclusiveVatCalculator::class)->calculate(2000, 0))
        ->toBe([
            'net_baisa' => 2000,
            'vat_baisa' => 0,
            'gross_baisa' => 2000,
        ]);
});
