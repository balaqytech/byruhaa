<?php

namespace App\Support;

class MoneyFormatter
{
    public static function baisa(int $amountBaisa, string $currency = 'OMR'): string
    {
        $amount = number_format($amountBaisa / 1000, 3);
        $currency = strtoupper($currency);

        return $currency === 'OMR'
            ? 'OMR '.$amount
            : $amount.' '.$currency;
    }
}
