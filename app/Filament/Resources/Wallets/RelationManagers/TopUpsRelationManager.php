<?php

namespace App\Filament\Resources\Wallets\RelationManagers;

use App\Filament\Resources\Payments\PaymentResource;
use App\Modules\Finance\Models\WalletTopUp;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TopUpsRelationManager extends RelationManager
{
    protected static string $relationship = 'topUps';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin_wallets.top_ups');
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('reference')->label(__('admin_wallets.top_up'))->searchable()->copyable(),
            TextColumn::make('payment.reference')->label(__('admin_wallets.payment'))->searchable()->placeholder('-')
                ->url(fn (WalletTopUp $record): ?string => $record->payment_id ? PaymentResource::getUrl('view', ['record' => $record->payment_id]) : null),
            TextColumn::make('status')->label(__('admin.fields.status'))->badge()->formatStateUsing(fn (string $state): string => __('admin_wallets.statuses.'.$state)),
            TextColumn::make('amount_baisa')->label(__('admin.fields.amount'))->money('OMR', divideBy: 1000),
            TextColumn::make('available_baisa')->label(__('admin_wallets.available'))->state(fn (WalletTopUp $record): int => in_array($record->status, ['credited', 'refunding'], true) ? max(0, $record->spendable_baisa - $record->reserved_refund_baisa) : 0)->money('OMR', divideBy: 1000),
            TextColumn::make('eligible_refund_baisa')->label(__('admin_wallets.refundable'))->state(fn (WalletTopUp $record): int => in_array($record->status, ['credited', 'refunding'], true) && $record->refund_deadline_at?->isFuture() ? max(0, $record->refundable_baisa - $record->reserved_refund_baisa) : 0)->money('OMR', divideBy: 1000),
            TextColumn::make('reserved_refund_baisa')->label(__('admin_wallets.reserved'))->money('OMR', divideBy: 1000),
            TextColumn::make('refund_deadline_at')->label(__('admin_wallets.refund_deadline'))->dateTime()->placeholder('-'),
            TextColumn::make('credited_at')->label(__('admin_wallets.credited_at'))->dateTime()->placeholder('-'),
        ])->filters([SelectFilter::make('status')->label(__('admin.fields.status'))->options(__('admin_wallets.top_up_statuses'))])
            ->headerActions([])->recordActions([])->toolbarActions([]);
    }
}
