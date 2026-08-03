<?php

namespace App\Filament\Resources\Coupons\Tables;

use App\Enums\CouponType;
use App\Modules\Events\Models\Coupon;
use App\Support\MoneyFormatter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('admin.fields.coupon_code'))
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('event.name')
                    ->label(__('admin.fields.event'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('admin.fields.type'))
                    ->badge(),
                TextColumn::make('value')
                    ->label(__('admin.fields.discount_amount'))
                    ->state(fn (Coupon $record): string => self::value($record)),
                TextColumn::make('minimum_family_members')
                    ->label(__('admin.fields.minimum_family_members')),
                TextColumn::make('maximum_family_members')
                    ->label(__('admin.fields.maximum_family_members'))
                    ->placeholder('-'),
                TextColumn::make('usage')
                    ->label(__('admin.fields.usage'))
                    ->state(fn (Coupon $record): string => self::usage($record)),
                TextColumn::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->formatStateUsing(fn (bool $state): string => $state ? __('admin.statuses.active') : __('admin.statuses.inactive'))
                    ->badge(),
                TextColumn::make('expires_at')
                    ->label(__('admin.fields.expires_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin.fields.is_active')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function value(Coupon $record): string
    {
        if ($record->type === CouponType::PercentagePerMember) {
            return number_format(((int) $record->percentage_basis_points) / 100, 2).'%';
        }

        return MoneyFormatter::baisa((int) $record->amount_baisa, $record->currency);
    }

    private static function usage(Coupon $record): string
    {
        $maximumUses = $record->maximum_uses === null ? __('admin.fields.unlimited') : (string) $record->maximum_uses;

        return $record->activeRedemptionsCount().' / '.$maximumUses;
    }
}
