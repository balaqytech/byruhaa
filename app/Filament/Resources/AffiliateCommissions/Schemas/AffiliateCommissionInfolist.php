<?php

namespace App\Filament\Resources\AffiliateCommissions\Schemas;

use App\Filament\Resources\Affiliates\AffiliateResource;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\LedgerTransactions\LedgerTransactionResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\AffiliateCommission;
use App\Support\MoneyFormatter;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class AffiliateCommissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make(__('admin.resources.affiliate_commissions.label'))
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('admin.resources.affiliate_commissions.label'))
                            ->schema([
                                Section::make(__('admin.resources.affiliate_commissions.label'))
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('affiliate.name')
                                            ->label(__('admin.fields.affiliate'))
                                            ->url(fn (AffiliateCommission $record): string => AffiliateResource::getUrl('view', ['record' => $record->affiliate])),
                                        TextEntry::make('booking.reference')
                                            ->label(__('admin.fields.booking'))
                                            ->url(fn (AffiliateCommission $record): string => BookingResource::getUrl('view', ['record' => $record->booking])),
                                        TextEntry::make('payment.reference')
                                            ->label(__('admin.fields.payment'))
                                            ->url(fn (AffiliateCommission $record): string => PaymentResource::getUrl('view', ['record' => $record->payment]))
                                            ->copyable(),
                                        TextEntry::make('base_amount_baisa')
                                            ->label(__('admin.fields.base_amount'))
                                            ->state(fn (AffiliateCommission $record): string => MoneyFormatter::baisa($record->base_amount_baisa, $record->currency)),
                                        TextEntry::make('commission_amount_baisa')
                                            ->label(__('admin.fields.commission_amount'))
                                            ->state(fn (AffiliateCommission $record): string => MoneyFormatter::baisa($record->commission_amount_baisa, $record->currency)),
                                        TextEntry::make('commission_rate_basis_points')
                                            ->label(__('admin.fields.commission_rate'))
                                            ->state(fn (AffiliateCommission $record): string => ((string) ($record->commission_rate_basis_points / 100)).'%'),
                                        TextEntry::make('earned_at')
                                            ->label(__('admin.fields.earned_at'))
                                            ->dateTime(),
                                        TextEntry::make('currency')
                                            ->label(__('admin.fields.currency')),
                                    ]),
                            ]),
                        Tab::make(__('admin.resources.ledger_transactions.label'))
                            ->schema([
                                Section::make(__('admin.resources.ledger_transactions.label'))
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('ledgerTransaction.reference')
                                            ->label(__('admin.fields.reference'))
                                            ->url(fn (AffiliateCommission $record): ?string => $record->ledgerTransaction
                                                ? LedgerTransactionResource::getUrl('view', ['record' => $record->ledgerTransaction])
                                                : null)
                                            ->placeholder('-'),
                                        TextEntry::make('ledgerTransaction.total_baisa')
                                            ->label(__('admin.fields.total'))
                                            ->state(fn (AffiliateCommission $record): string => $record->ledgerTransaction
                                                ? MoneyFormatter::baisa($record->ledgerTransaction->total_baisa, $record->ledgerTransaction->currency)
                                                : '-'),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
