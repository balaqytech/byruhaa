<?php

namespace App\Filament\Resources\Wallets\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WalletsTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('id')->label(__('admin_wallets.id'))->sortable(),
            TextColumn::make('minorProfile.familyMember.name')->label(__('admin_wallets.minor'))->searchable(),
            TextColumn::make('minorProfile.member_code')->label(__('admin_wallets.member_code'))->searchable()->copyable(),
            TextColumn::make('minorProfile.familyMember.customer.name')->label(__('admin_wallets.guardian'))->searchable(),
            TextColumn::make('minorProfile.familyMember.customer.phone_number')->label(__('admin.fields.phone_number'))->searchable(),
            TextColumn::make('balance_baisa')->label(__('admin_wallets.balance'))->money('OMR', divideBy: 1000)->sortable(),
            TextColumn::make('status')->label(__('admin.fields.status'))->badge()->formatStateUsing(fn (string $state): string => __('admin_wallets.statuses.'.$state)),
            TextColumn::make('created_at')->label(__('admin.fields.created_at'))->dateTime()->sortable(),
        ])->filters([
            SelectFilter::make('status')->label(__('admin.fields.status'))->options(__('admin_wallets.wallet_statuses')),
        ])->recordActions([ViewAction::make()])->toolbarActions([]);
    }
}
