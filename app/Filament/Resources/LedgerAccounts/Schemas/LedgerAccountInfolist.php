<?php

namespace App\Filament\Resources\LedgerAccounts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LedgerAccountInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.resources.ledger_accounts.label'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('code')
                            ->label(__('admin.fields.code'))
                            ->copyable(),
                        TextEntry::make('name')
                            ->label(__('admin.fields.name')),
                        TextEntry::make('type')
                            ->label(__('admin.fields.type'))
                            ->badge(),
                        TextEntry::make('currency')
                            ->label(__('admin.fields.currency')),
                        TextEntry::make('is_active')
                            ->label(__('admin.fields.is_active'))
                            ->formatStateUsing(fn (bool $state): string => $state ? __('admin.statuses.active') : __('admin.statuses.inactive'))
                            ->badge(),
                        TextEntry::make('created_at')
                            ->label(__('admin.fields.created_at'))
                            ->dateTime(),
                    ]),
            ]);
    }
}
