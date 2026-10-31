<?php

namespace App\Filament\Resources\CashClosures;

use App\Filament\Resources\CashClosures\Pages\ListCashClosures;
use App\Filament\Resources\ShieldResource;
use App\Modules\Finance\Models\CashClosure;
use App\Modules\Identity\Models\User;
use App\Support\MoneyFormatter;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CashClosureResource extends ShieldResource
{
    protected static ?string $model = CashClosure::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function getModelLabel(): string
    {
        return 'إقفال Cash';
    }

    public static function getPluralModelLabel(): string
    {
        return 'إقفالات Cash اليومية';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.finance');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canClose(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && ($user->isPanelAdministrator() || $user->can('Create:CashClosure'));
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with('cashier')->latest('business_date'))
            ->columns([
                TextColumn::make('business_date')->label('اليوم')->date()->sortable(),
                TextColumn::make('cashier.name')->label('الكاشير')->searchable(),
                TextColumn::make('receipts_baisa')->label('قبض POS')->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR')),
                TextColumn::make('refunds_baisa')->label('Refund Cash')->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR')),
                TextColumn::make('withdrawals_baisa')->label('Withdrawal Cash')->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR')),
                TextColumn::make('expected_baisa')->label('المتوقع')->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR')),
                TextColumn::make('counted_baisa')->label('المعدود')->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR')),
                TextColumn::make('difference_baisa')->label('الفرق')->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR')),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('issues')->label('ملاحظات المطابقة')->formatStateUsing(fn ($state): string => implode('، ', (array) $state))->wrap(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCashClosures::route('/')];
    }
}
