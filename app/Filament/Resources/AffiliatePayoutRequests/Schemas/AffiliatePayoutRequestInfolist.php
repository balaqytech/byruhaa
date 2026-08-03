<?php

namespace App\Filament\Resources\AffiliatePayoutRequests\Schemas;

use App\Filament\Resources\Affiliates\AffiliateResource;
use App\Filament\Resources\LedgerTransactions\LedgerTransactionResource;
use App\Modules\Affiliates\Models\AffiliatePayoutRequest;
use App\Support\MoneyFormatter;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class AffiliatePayoutRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make(__('admin.resources.affiliate_payout_requests.label'))
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('admin.resources.affiliate_payout_requests.label'))
                            ->schema([
                                Section::make(__('admin.resources.affiliate_payout_requests.label'))
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('reference')
                                            ->label(__('admin.fields.reference'))
                                            ->copyable(),
                                        TextEntry::make('status')
                                            ->label(__('admin.fields.status'))
                                            ->badge(),
                                        TextEntry::make('amount_baisa')
                                            ->label(__('admin.fields.amount'))
                                            ->state(fn (AffiliatePayoutRequest $record): string => MoneyFormatter::baisa($record->amount_baisa, $record->currency)),
                                        TextEntry::make('affiliate.name')
                                            ->label(__('admin.fields.affiliate'))
                                            ->url(fn (AffiliatePayoutRequest $record): string => AffiliateResource::getUrl('view', ['record' => $record->affiliate])),
                                        TextEntry::make('currency')
                                            ->label(__('admin.fields.currency')),
                                        TextEntry::make('created_at')
                                            ->label(__('admin.fields.created_at'))
                                            ->dateTime(),
                                        TextEntry::make('affiliate_notes')
                                            ->label(__('admin.fields.affiliate_notes'))
                                            ->placeholder('-')
                                            ->columnSpanFull(),
                                        TextEntry::make('admin_notes')
                                            ->label(__('admin.fields.admin_notes'))
                                            ->placeholder('-')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make(__('admin.fields.payment_details'))
                            ->schema([
                                Section::make(__('admin.fields.payment_details'))
                                    ->schema([
                                        KeyValueEntry::make('payment_details')
                                            ->label(__('admin.fields.payment_details'))
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make(__('admin.navigation.finance'))
                            ->schema([
                                Section::make(__('admin.navigation.finance'))
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('approver.name')
                                            ->label(__('admin.fields.approved_by'))
                                            ->placeholder('-'),
                                        TextEntry::make('approved_at')
                                            ->label(__('admin.fields.approved_at'))
                                            ->dateTime()
                                            ->placeholder('-'),
                                        TextEntry::make('payer.name')
                                            ->label(__('admin.fields.paid_by'))
                                            ->placeholder('-'),
                                        TextEntry::make('paid_at')
                                            ->label(__('admin.fields.paid_at'))
                                            ->dateTime()
                                            ->placeholder('-'),
                                        TextEntry::make('rejecter.name')
                                            ->label(__('admin.fields.rejected_by'))
                                            ->placeholder('-'),
                                        TextEntry::make('rejected_at')
                                            ->label(__('admin.fields.rejected_at'))
                                            ->dateTime()
                                            ->placeholder('-'),
                                        TextEntry::make('ledgerTransaction.reference')
                                            ->label(__('admin.resources.ledger_transactions.label'))
                                            ->url(fn (AffiliatePayoutRequest $record): ?string => $record->ledgerTransaction
                                                ? LedgerTransactionResource::getUrl('view', ['record' => $record->ledgerTransaction])
                                                : null)
                                            ->placeholder('-'),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
