<?php

namespace App\Filament\Resources\ThawaniSettlements;

use App\Filament\Resources\ShieldResource;
use App\Filament\Resources\ThawaniSettlements\Pages\ListThawaniSettlements;
use App\Modules\Finance\Models\ThawaniSettlement;
use App\Modules\Identity\Models\User;
use App\Support\MoneyFormatter;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ThawaniSettlementResource extends ShieldResource
{
    protected static ?string $model = ThawaniSettlement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    public static function getModelLabel(): string
    {
        return 'تسوية Thawani';
    }

    public static function getPluralModelLabel(): string
    {
        return 'تسويات Thawani';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.finance');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canRecord(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && ($user->isPanelAdministrator() || $user->can('Create:ThawaniSettlement'));
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with('recorder')->latest('settled_at'))
            ->columns([
                TextColumn::make('provider_reference')->label('مرجع التسوية')->searchable()->copyable(),
                TextColumn::make('settled_at')->label('تاريخ التسوية')->dateTime()->sortable(),
                TextColumn::make('gross_baisa')->label('الإجمالي')->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR')),
                TextColumn::make('fee_baisa')->label('رسوم Thawani')->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR')),
                TextColumn::make('bank_net_baisa')->label('Bank الصافي')->formatStateUsing(fn (int $state): string => MoneyFormatter::baisa($state, 'OMR')),
                TextColumn::make('recorder.name')->label('المسجل'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListThawaniSettlements::route('/')];
    }
}
