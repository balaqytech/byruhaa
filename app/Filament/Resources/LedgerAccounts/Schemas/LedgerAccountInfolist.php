<?php

namespace App\Filament\Resources\LedgerAccounts\Schemas;

use App\Enums\LedgerAccountType;
use App\Filament\Resources\LedgerTransactions\LedgerTransactionResource;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Support\MoneyFormatter;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LedgerAccountInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.resources.ledger_accounts.label'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('code')
                            ->label(__('admin.fields.code'))
                            ->copyable(),
                        TextEntry::make('name')
                            ->label(__('admin.fields.name')),
                        TextEntry::make('type')
                            ->label(__('admin.fields.type'))
                            ->formatStateUsing(fn (LedgerAccountType $state): string => $state->label())
                            ->badge(),
                        TextEntry::make('currency')
                            ->label(__('admin.fields.currency')),
                        TextEntry::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->formatStateUsing(fn (bool $state): string => $state ? __('admin.statuses.active') : __('admin.statuses.inactive'))
                            ->badge(),
                        TextEntry::make('created_at')
                            ->label(__('admin.fields.created_at'))
                            ->dateTime(),
                    ]),
                Section::make(__('admin.fields.entries'))
                    ->schema([
                        TextEntry::make('entries')
                            ->label(__('admin.fields.entries'))
                            ->state(fn (LedgerAccount $record): array => $record->entries
                                ->map(fn (LedgerEntry $entry): string => self::entrySummary($entry))
                                ->all())
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function entrySummary(LedgerEntry $entry): string
    {
        $transaction = $entry->ledgerTransaction;
        $debit = MoneyFormatter::baisa($entry->debit_baisa, $entry->currency);
        $credit = MoneyFormatter::baisa($entry->credit_baisa, $entry->currency);
        $url = LedgerTransactionResource::getUrl('view', ['record' => $transaction]);

        return "{$transaction->reference} | {$debit} | {$credit} | {$entry->memo} | {$url}";
    }
}
