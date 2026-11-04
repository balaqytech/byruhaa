<?php

namespace App\Http\Resources\Integrations\Uchat;

use App\Support\Money\MoneyFactory;
use Brick\Money\Money;

trait FormatsUchatMoney
{
    protected function money(Money|int|null $amount, ?string $currency = null): ?int
    {
        return $amount instanceof Money ? MoneyFactory::toMinor($amount) : $amount;
    }
}
