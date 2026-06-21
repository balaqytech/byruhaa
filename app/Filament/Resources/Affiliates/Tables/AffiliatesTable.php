<?php

namespace App\Filament\Resources\Affiliates\Tables;

use App\Enums\AffiliateStatus;
use App\Models\Affiliate;
use App\Support\MoneyFormatter;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AffiliatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withCount(['referrals', 'commissions'])
                ->latest('id'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('admin.fields.email_address'))
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('phone_number')
                    ->label(__('admin.fields.phone_number'))
                    ->searchable(),
                TextColumn::make('code')
                    ->label(__('admin.fields.code'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('referrals_count')
                    ->label(__('admin.fields.referrals'))
                    ->sortable(),
                TextColumn::make('commissions_count')
                    ->label(__('admin.fields.commissions'))
                    ->sortable(),
                TextColumn::make('available_balance')
                    ->label(__('admin.fields.available_balance'))
                    ->state(fn (Affiliate $record): string => MoneyFormatter::baisa($record->availableBalanceBaisa())),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.fields.status'))
                    ->options(AffiliateStatus::class),
            ])
            ->recordActions([
                self::approveAction(),
                self::rejectAction(),
                self::suspendAction(),
                self::reactivateAction(),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function approveAction(): Action
    {
        return Action::make('approve')
            ->label(__('admin.actions.approve'))
            ->color('success')
            ->visible(fn (Affiliate $record): bool => $record->status === AffiliateStatus::Pending)
            ->requiresConfirmation()
            ->action(fn (Affiliate $record): Affiliate => self::setStatus($record, AffiliateStatus::Approved));
    }

    public static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label(__('admin.actions.reject'))
            ->color('danger')
            ->visible(fn (Affiliate $record): bool => $record->status === AffiliateStatus::Pending)
            ->requiresConfirmation()
            ->action(fn (Affiliate $record): Affiliate => self::setStatus($record, AffiliateStatus::Rejected));
    }

    public static function suspendAction(): Action
    {
        return Action::make('suspend')
            ->label(__('admin.actions.suspend'))
            ->color('warning')
            ->visible(fn (Affiliate $record): bool => $record->status === AffiliateStatus::Approved)
            ->requiresConfirmation()
            ->action(fn (Affiliate $record): Affiliate => self::setStatus($record, AffiliateStatus::Suspended));
    }

    public static function reactivateAction(): Action
    {
        return Action::make('reactivate')
            ->label(__('admin.actions.reactivate'))
            ->color('success')
            ->visible(fn (Affiliate $record): bool => in_array($record->status, [AffiliateStatus::Rejected, AffiliateStatus::Suspended], true))
            ->requiresConfirmation()
            ->action(fn (Affiliate $record): Affiliate => self::setStatus($record, AffiliateStatus::Approved));
    }

    private static function setStatus(Affiliate $affiliate, AffiliateStatus $status): Affiliate
    {
        $affiliate->forceFill([
            'status' => $status,
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at' => now(),
        ])->save();

        Notification::make()
            ->title(__('admin.resources.affiliates.label').' '.$status->getLabel())
            ->success()
            ->send();

        return $affiliate;
    }
}
