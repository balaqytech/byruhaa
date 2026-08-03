<?php

namespace App\Filament\Resources\AffiliatePayoutRequests\Tables;

use App\Modules\Affiliates\Actions\PostAffiliatePayoutLedgerTransaction;
use App\Enums\AffiliatePayoutRequestStatus;
use App\Filament\Resources\Affiliates\AffiliateResource;
use App\Modules\Affiliates\Models\AffiliatePayoutRequest;
use App\Support\MoneyFormatter;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AffiliatePayoutRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['affiliate', 'ledgerTransaction'])
                ->latest('id'))
            ->columns([
                TextColumn::make('reference')
                    ->label(__('admin.fields.reference'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('affiliate.name')
                    ->label(__('admin.fields.affiliate'))
                    ->searchable()
                    ->url(fn (AffiliatePayoutRequest $record): string => AffiliateResource::getUrl('view', ['record' => $record->affiliate])),
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
                TextColumn::make('paid_at')
                    ->label(__('admin.fields.paid_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.fields.status'))
                    ->options(AffiliatePayoutRequestStatus::class),
            ])
            ->recordActions([
                self::approveAction(),
                self::rejectAction(),
                self::markPaidAction(),
                ViewAction::make(),
            ]);
    }

    public static function approveAction(): Action
    {
        return Action::make('approve')
            ->label(__('admin.actions.approve'))
            ->color('success')
            ->visible(fn (AffiliatePayoutRequest $record): bool => $record->status === AffiliatePayoutRequestStatus::Pending)
            ->requiresConfirmation()
            ->action(function (AffiliatePayoutRequest $record): void {
                $record->forceFill([
                    'status' => AffiliatePayoutRequestStatus::Approved,
                    'approved_by_user_id' => auth()->id(),
                    'approved_at' => now(),
                    'rejected_by_user_id' => null,
                    'rejected_at' => null,
                ])->save();

                self::notify();
            });
    }

    public static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label(__('admin.actions.reject'))
            ->color('danger')
            ->visible(fn (AffiliatePayoutRequest $record): bool => $record->status === AffiliatePayoutRequestStatus::Pending)
            ->requiresConfirmation()
            ->action(function (AffiliatePayoutRequest $record): void {
                $record->forceFill([
                    'status' => AffiliatePayoutRequestStatus::Rejected,
                    'rejected_by_user_id' => auth()->id(),
                    'rejected_at' => now(),
                ])->save();

                self::notify();
            });
    }

    public static function markPaidAction(): Action
    {
        return Action::make('mark_paid')
            ->label(__('admin.actions.mark_paid'))
            ->color('success')
            ->visible(fn (AffiliatePayoutRequest $record): bool => $record->status === AffiliatePayoutRequestStatus::Approved)
            ->requiresConfirmation()
            ->action(function (AffiliatePayoutRequest $record, PostAffiliatePayoutLedgerTransaction $postLedgerTransaction): void {
                $record->forceFill([
                    'status' => AffiliatePayoutRequestStatus::Paid,
                    'paid_by_user_id' => auth()->id(),
                    'paid_at' => now(),
                ])->save();

                $postLedgerTransaction->execute($record->refresh());

                self::notify();
            });
    }

    private static function notify(): void
    {
        Notification::make()
            ->title(__('admin.resources.affiliate_payout_requests.label').' updated')
            ->success()
            ->send();
    }
}
