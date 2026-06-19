<?php

namespace App\Enums;

enum LedgerAccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Asset => __('admin.ledger_account_types.asset'),
            self::Liability => __('admin.ledger_account_types.liability'),
            self::Equity => __('admin.ledger_account_types.equity'),
            self::Income => __('admin.ledger_account_types.income'),
            self::Expense => __('admin.ledger_account_types.expense'),
        };
    }
}
