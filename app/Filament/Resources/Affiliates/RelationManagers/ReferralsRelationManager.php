<?php

namespace App\Filament\Resources\Affiliates\RelationManagers;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\AffiliateReferral;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReferralsRelationManager extends RelationManager
{
    protected static string $relationship = 'referrals';

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('booking.reference')
                    ->label(__('admin.fields.booking')),
                TextEntry::make('affiliate_code')
                    ->label(__('admin.fields.affiliate_code'))
                    ->copyable(),
                TextEntry::make('affiliate_name')
                    ->label(__('admin.fields.affiliate_name')),
                TextEntry::make('captured_at')
                    ->label(__('admin.fields.captured_at'))
                    ->dateTime(),
                TextEntry::make('expires_at')
                    ->label(__('admin.fields.expires_at'))
                    ->dateTime(),
                TextEntry::make('attributed_at')
                    ->label(__('admin.fields.attributed_at'))
                    ->dateTime(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('affiliate_code')
            ->columns([
                TextColumn::make('booking.reference')
                    ->label(__('admin.fields.booking'))
                    ->searchable()
                    ->url(fn (AffiliateReferral $record): string => BookingResource::getUrl('view', ['record' => $record->booking])),
                TextColumn::make('affiliate_code')
                    ->label(__('admin.fields.affiliate_code'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('affiliate_name')
                    ->label(__('admin.fields.affiliate_name'))
                    ->searchable(),
                TextColumn::make('attributed_at')
                    ->label(__('admin.fields.attributed_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.fields.referrals');
    }
}
