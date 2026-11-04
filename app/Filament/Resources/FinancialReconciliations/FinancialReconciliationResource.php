<?php

namespace App\Filament\Resources\FinancialReconciliations;

use App\Filament\Resources\FinancialReconciliations\Pages\ListFinancialReconciliations;
use App\Filament\Resources\ShieldResource;
use App\Modules\Finance\Models\FinancialReconciliationRun;
use App\Modules\Identity\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use UnitEnum;

class FinancialReconciliationResource extends ShieldResource
{
    protected static ?string $model = FinancialReconciliationRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    public static function getModelLabel(): string
    {
        return 'مطابقة مالية';
    }

    public static function getPluralModelLabel(): string
    {
        return 'المطابقات المالية';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.finance');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canRun(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && ($user->isPanelAdministrator() || $user->can('Create:FinancialReconciliationRun'));
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->latest('id'))
            ->columns([
                TextColumn::make('as_of_date')->label('حتى تاريخ')->date()->sortable(),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('discrepancy_count')->label('الفروق'),
                TextColumn::make('pending_count')->label('المعلقة'),
                TextColumn::make('created_at')->label('وقت الفحص')->dateTime()->sortable(),
            ])
            ->recordActions([
                Action::make('issues')->label('عرض النتائج')
                    ->modalContent(fn (FinancialReconciliationRun $record): View => view('filament.finance.reconciliation-issues', ['issues' => $record->issues]))
                    ->modalSubmitAction(false),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListFinancialReconciliations::route('/')];
    }
}
