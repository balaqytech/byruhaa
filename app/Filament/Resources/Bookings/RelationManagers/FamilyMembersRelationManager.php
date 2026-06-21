<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FamilyMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'familyMembers';

    protected static ?string $title = 'Family members';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('familyMember.name')
            ->columns([
                TextColumn::make('familyMember.name')
                    ->label(__('admin.fields.name'))
                    ->searchable(),
                TextColumn::make('familyMember.school_name')
                    ->label(__('ui.fields.school_name'))
                    ->placeholder('-'),
                TextColumn::make('familyMember.grade')
                    ->label(__('ui.fields.grade'))
                    ->placeholder('-'),
                TextColumn::make('contract.state')
                    ->label(__('admin.fields.state'))
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('contract.signed_at')
                    ->label(__('admin.contract_variables.labels.contract_signed_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
