<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FamilyMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'familyMembers';

    protected static ?string $title = 'Family members';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin.fields.name'))
                    ->required()
                    ->maxLength(255),
                DatePicker::make('birth_date')
                    ->label(__('ui.fields.birth_date'))
                    ->required(),
                TextInput::make('school_name')
                    ->label(__('ui.fields.school_name'))
                    ->maxLength(255),
                TextInput::make('grade')
                    ->label(__('ui.fields.grade'))
                    ->maxLength(255),
                Textarea::make('medical_notes')
                    ->label(__('ui.fields.medical_notes'))
                    ->columnSpanFull(),
                TextInput::make('emergency_contact_name')
                    ->label(__('ui.fields.emergency_contact_name'))
                    ->maxLength(255),
                TextInput::make('emergency_contact_phone')
                    ->label(__('ui.fields.emergency_contact_phone'))
                    ->tel()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable(),
                TextColumn::make('birth_date')
                    ->label(__('ui.fields.birth_date'))
                    ->date(),
                TextColumn::make('school_name')
                    ->label(__('ui.fields.school_name'))
                    ->placeholder('-'),
                TextColumn::make('grade')
                    ->label(__('ui.fields.grade'))
                    ->placeholder('-'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
