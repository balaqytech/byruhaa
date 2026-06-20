<?php

namespace App\Filament\Resources\LedgerTransactions\Schemas;

use App\Filament\Resources\LedgerAccounts\LedgerAccountResource;
use App\Filament\Resources\PaymentRefunds\PaymentRefundResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Support\MoneyFormatter;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LedgerTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.resources.ledger_transactions.label'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('reference')
                            ->label(__('admin.fields.reference'))
                            ->copyable(),
                        TextEntry::make('description')
                            ->label(__('admin.fields.description'))
                            ->columnSpan(2),
                        TextEntry::make('source')
                            ->label(__('admin.fields.source'))
                            ->state(fn (LedgerTransaction $record): string => self::sourceLabel($record))
                            ->url(fn (LedgerTransaction $record): ?string => self::sourceUrl($record))
                            ->placeholder('-'),
                        TextEntry::make('total_baisa')
                            ->label(__('admin.fields.total'))
                            ->state(fn (LedgerTransaction $record): string => MoneyFormatter::baisa($record->total_baisa, $record->currency)),
                        TextEntry::make('occurred_at')
                            ->label(__('admin.fields.occurred_at'))
                            ->dateTime(),
                    ]),
                Section::make(__('admin.fields.entries'))
                    ->schema([
                        TextEntry::make('entries')
                            ->label(__('admin.fields.entries'))
                            ->state(fn (LedgerTransaction $record): array => $record->entries
                                ->map(fn (LedgerEntry $entry): string => self::entrySummary($entry))
                                ->all())
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function sourceLabel(LedgerTransaction $record): string
    {
        $source = $record->source;

        if ($source instanceof Payment || $source instanceof PaymentRefund) {
            return $source->reference;
        }

        return '-';
    }

    private static function sourceUrl(LedgerTransaction $record): ?string
    {
        return match (true) {
            $record->source instanceof Payment => PaymentResource::getUrl('view', ['record' => $record->source]),
            $record->source instanceof PaymentRefund => PaymentRefundResource::getUrl('view', ['record' => $record->source]),
            default => null,
        };
    }

    private static function entrySummary(LedgerEntry $entry): string
    {
        $account = $entry->ledgerAccount;
        $debit = MoneyFormatter::baisa($entry->debit_baisa, $entry->currency);
        $credit = MoneyFormatter::baisa($entry->credit_baisa, $entry->currency);
        $url = LedgerAccountResource::getUrl('view', ['record' => $account]);

        return "{$account->code} {$account->name} | {$debit} | {$credit} | {$entry->memo} | {$url}";
    }
}
