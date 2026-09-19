<?php

namespace App\Filament\Resources\MinorProfiles;

use App\Enums\UserRole;
use App\Filament\Resources\MinorProfiles\Pages\ListMinorProfiles;
use App\Filament\Resources\MinorProfiles\Pages\ViewMinorProfile;
use App\Filament\Resources\MinorProfiles\Schemas\MinorProfileInfolist;
use App\Filament\Resources\MinorProfiles\Tables\MinorProfilesTable;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Identity\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** @extends resource<MinorProfile> */
class MinorProfileResource extends Resource
{
    protected static ?string $model = MinorProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'member_code';

    public static function getModelLabel(): string
    {
        return __('admin_minors.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin_minors.plural_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.customer_management');
    }

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && in_array($user->role, [UserRole::Admin, UserRole::Staff], true);
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /** @return Builder<MinorProfile> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('familyMember.customer');
    }

    public static function infolist(Schema $schema): Schema
    {
        return MinorProfileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MinorProfilesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMinorProfiles::route('/'),
            'view' => ViewMinorProfile::route('/{record}'),
        ];
    }
}
