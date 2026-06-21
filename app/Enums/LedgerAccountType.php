<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LedgerAccountType: string implements HasColor, HasLabel
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Income = 'income';
    case Expense = 'expense';

    public function getLabel(): string
    {
        return match ($this) {
            self::Asset => __('admin.ledger_account_types.asset'),
            self::Liability => __('admin.ledger_account_types.liability'),
            self::Equity => __('admin.ledger_account_types.equity'),
            self::Income => __('admin.ledger_account_types.income'),
            self::Expense => __('admin.ledger_account_types.expense'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Asset => 'info',
            self::Liability => 'warning',
            self::Equity => 'gray',
            self::Income => 'success',
            self::Expense => 'danger',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
