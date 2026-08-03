<?php

namespace App\Filament\Resources\AffiliateCommissions\Tables;

use App\Filament\Resources\Affiliates\AffiliateResource;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Modules\Affiliates\Models\AffiliateCommission;
use App\Support\MoneyFormatter;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AffiliateCommissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['affiliate', 'booking', 'payment', 'ledgerTransaction'])
                ->latest('earned_at'))
            ->columns([
                TextColumn::make('affiliate.name')
                    ->label(__('admin.fields.affiliate'))
                    ->searchable()
                    ->url(fn (AffiliateCommission $record): string => AffiliateResource::getUrl('view', ['record' => $record->affiliate])),
                TextColumn::make('booking.reference')
                    ->label(__('admin.fields.booking'))
                    ->searchable()
                    ->url(fn (AffiliateCommission $record): string => BookingResource::getUrl('view', ['record' => $record->booking])),
                TextColumn::make('payment.reference')
                    ->label(__('admin.fields.payment'))
                    ->searchable()
                    ->copyable()
                    ->url(fn (AffiliateCommission $record): string => PaymentResource::getUrl('view', ['record' => $record->payment])),
                TextColumn::make('base_amount_baisa')
                    ->label(__('admin.fields.base_amount'))
                    ->formatStateUsing(fn (int $state, AffiliateCommission $record): string => MoneyFormatter::baisa($state, $record->currency))
                    ->sortable(),
                TextColumn::make('commission_amount_baisa')
                    ->label(__('admin.fields.commission_amount'))
                    ->formatStateUsing(fn (int $state, AffiliateCommission $record): string => MoneyFormatter::baisa($state, $record->currency))
                    ->sortable(),
                TextColumn::make('earned_at')
                    ->label(__('admin.fields.earned_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
