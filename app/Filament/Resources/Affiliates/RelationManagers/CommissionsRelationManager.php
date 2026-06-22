<?php

namespace App\Filament\Resources\Affiliates\RelationManagers;

use App\Filament\Resources\AffiliateCommissions\AffiliateCommissionResource;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\AffiliateCommission;
use App\Support\MoneyFormatter;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CommissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'commissions';

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('payment.reference')
                    ->label(__('admin.fields.payment')),
                TextEntry::make('booking.reference')
                    ->label(__('admin.fields.booking')),
                TextEntry::make('base_amount_baisa')
                    ->label(__('admin.fields.base_amount'))
                    ->state(fn (AffiliateCommission $record): string => MoneyFormatter::baisa($record->base_amount_baisa, $record->currency)),
                TextEntry::make('commission_amount_baisa')
                    ->label(__('admin.fields.commission_amount'))
                    ->state(fn (AffiliateCommission $record): string => MoneyFormatter::baisa($record->commission_amount_baisa, $record->currency)),
                TextEntry::make('earned_at')
                    ->label(__('admin.fields.earned_at'))
                    ->dateTime(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('payment.reference')
                    ->label(__('admin.fields.payment'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('booking.reference')
                    ->label(__('admin.fields.booking'))
                    ->searchable()
                    ->url(fn (AffiliateCommission $record): string => BookingResource::getUrl('view', ['record' => $record->booking])),
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
                ViewAction::make()
                    ->url(fn (AffiliateCommission $record): string => AffiliateCommissionResource::getUrl('view', ['record' => $record])),
            ]);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.fields.commissions');
    }
}
