<?php

namespace App\Modules\Store\Services;

final class InclusiveVatCalculator
{
    /**
     * @return array{net_baisa: int, vat_baisa: int, gross_baisa: int}
     */
    public function calculate(int $grossBaisa, int $ratePercentage): array
    {
        if ($grossBaisa < 0) {
            throw new \InvalidArgumentException('Gross amount cannot be negative.');
        }

        if ($ratePercentage <= 0) {
            return [
                'net_baisa' => $grossBaisa,
                'vat_baisa' => 0,
                'gross_baisa' => $grossBaisa,
            ];
        }

        $denominator = 100 + $ratePercentage;
        $vatNumerator = $grossBaisa * $ratePercentage;
        $vatBaisa = intdiv($vatNumerator, $denominator);

        if (($vatNumerator % $denominator) * 2 >= $denominator) {
            $vatBaisa++;
        }

        return [
            'net_baisa' => $grossBaisa - $vatBaisa,
            'vat_baisa' => $vatBaisa,
            'gross_baisa' => $grossBaisa,
        ];
    }
}
