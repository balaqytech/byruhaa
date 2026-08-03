<?php

namespace App\Filament\Resources\Affiliates\Schemas;

use App\Modules\Affiliates\Models\Affiliate;
use App\Support\MoneyFormatter;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class AffiliateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make(__('admin.resources.affiliates.label'))
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('admin.resources.affiliates.label'))
                            ->schema([
                                Section::make(__('admin.resources.affiliates.label'))
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label(__('admin.fields.name')),
                                        TextEntry::make('status')
                                            ->label(__('admin.fields.status'))
                                            ->badge(),
                                        TextEntry::make('code')
                                            ->label(__('admin.fields.code'))
                                            ->copyable(),
                                        TextEntry::make('email')
                                            ->label(__('admin.fields.email_address'))
                                            ->placeholder('-'),
                                        TextEntry::make('phone_number')
                                            ->label(__('admin.fields.phone_number')),
                                        TextEntry::make('affiliate_link')
                                            ->label(__('admin.fields.affiliate_link'))
                                            ->state(fn (Affiliate $record): string => $record->affiliateLink())
                                            ->copyable(),
                                        TextEntry::make('reviewer.name')
                                            ->label(__('admin.fields.reviewer'))
                                            ->placeholder('-'),
                                        TextEntry::make('reviewed_at')
                                            ->label(__('admin.fields.reviewed_at'))
                                            ->dateTime()
                                            ->placeholder('-'),
                                        TextEntry::make('review_notes')
                                            ->label(__('admin.fields.review_notes'))
                                            ->placeholder('-')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make(__('admin.navigation.finance'))
                            ->schema([
                                Section::make(__('admin.navigation.finance'))
                                    ->columns(4)
                                    ->schema([
                                        TextEntry::make('earned_commission')
                                            ->label(__('admin.fields.earned_commission'))
                                            ->state(fn (Affiliate $record): string => MoneyFormatter::baisa($record->earnedCommissionBaisa())),
                                        TextEntry::make('available_balance')
                                            ->label(__('admin.fields.available_balance'))
                                            ->state(fn (Affiliate $record): string => MoneyFormatter::baisa($record->availableBalanceBaisa())),
                                        TextEntry::make('pending_payouts')
                                            ->label(__('admin.fields.pending_payouts'))
                                            ->state(fn (Affiliate $record): string => MoneyFormatter::baisa($record->pendingPayoutBaisa())),
                                        TextEntry::make('paid_payouts')
                                            ->label(__('admin.fields.paid_payouts'))
                                            ->state(fn (Affiliate $record): string => MoneyFormatter::baisa($record->paidPayoutBaisa())),
                                        TextEntry::make('referrals_count')
                                            ->label(__('admin.fields.referrals'))
                                            ->state(fn (Affiliate $record): int => $record->referrals()->count()),
                                        TextEntry::make('commissions_count')
                                            ->label(__('admin.fields.commissions'))
                                            ->state(fn (Affiliate $record): int => $record->commissions()->count()),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
