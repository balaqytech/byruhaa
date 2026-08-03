<?php

namespace App\Filament\Resources\Affiliates\RelationManagers;

use App\Enums\AffiliatePayoutRequestStatus;
use App\Filament\Resources\AffiliatePayoutRequests\AffiliatePayoutRequestResource;
use App\Filament\Resources\AffiliatePayoutRequests\Tables\AffiliatePayoutRequestsTable;
use App\Modules\Affiliates\Models\AffiliatePayoutRequest;
use App\Support\MoneyFormatter;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PayoutRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'payoutRequests';

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reference')
                    ->label(__('admin.fields.reference'))
                    ->copyable(),
                TextEntry::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge(),
                TextEntry::make('amount_baisa')
                    ->label(__('admin.fields.amount'))
                    ->state(fn (AffiliatePayoutRequest $record): string => MoneyFormatter::baisa($record->amount_baisa, $record->currency)),
                TextEntry::make('affiliate_notes')
                    ->label(__('admin.fields.affiliate_notes'))
                    ->placeholder('-'),
                TextEntry::make('admin_notes')
                    ->label(__('admin.fields.admin_notes'))
                    ->placeholder('-'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')
                    ->label(__('admin.fields.reference'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('amount_baisa')
                    ->label(__('admin.fields.amount'))
                    ->formatStateUsing(fn (int $state, AffiliatePayoutRequest $record): string => MoneyFormatter::baisa($state, $record->currency))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.fields.status'))
                    ->options(AffiliatePayoutRequestStatus::class),
            ])
            ->recordActions([
                AffiliatePayoutRequestsTable::approveAction(),
                AffiliatePayoutRequestsTable::rejectAction(),
                AffiliatePayoutRequestsTable::markPaidAction(),
                ViewAction::make()
                    ->url(fn (AffiliatePayoutRequest $record): string => AffiliatePayoutRequestResource::getUrl('view', ['record' => $record])),
            ]);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.fields.pending_payouts');
    }
}
