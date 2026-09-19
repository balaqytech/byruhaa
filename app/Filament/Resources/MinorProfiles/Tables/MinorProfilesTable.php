<?php

namespace App\Filament\Resources\MinorProfiles\Tables;

use App\Modules\Identity\Enums\MinorProfileStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MinorProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member_code')->label(__('admin_minors.member_code'))->searchable(),
                TextColumn::make('familyMember.name')->label(__('admin_minors.name'))->searchable(),
                TextColumn::make('familyMember.customer.name')->label(__('admin_minors.guardian'))->searchable(),
                TextColumn::make('status')->label(__('admin_minors.status'))->badge()
                    ->formatStateUsing(fn (MinorProfileStatus $state): string => __('admin_minors.statuses.'.$state->value)),
                IconColumn::make('wallet_spending_enabled')->label(__('admin_minors.wallet_spending_enabled'))->boolean(),
                IconColumn::make('direct_payment_enabled')->label(__('admin_minors.direct_payment_enabled'))->boolean(),
                TextColumn::make('activated_at')->label(__('admin_minors.activated_at'))->dateTime()->placeholder('-')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('admin_minors.status'))->options(__('admin_minors.statuses')),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([ViewAction::make()]);
    }
}
