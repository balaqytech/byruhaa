<?php

namespace App\Http\Resources\Api\V1\Concerns;

use App\Support\Money\MoneyFactory;
use Brick\Money\Money;

trait FormatsApiMoney
{
    protected function money(Money|int|null $amount, ?string $currency = null): ?string
    {
        if ($amount === null) {
            return null;
        }

        if ($amount instanceof Money) {
            return MoneyFactory::formatMoneyAmount($amount);
        }

        return MoneyFactory::formatMinorUnits($amount, $currency ?: MoneyFactory::OMR);
    }
}
