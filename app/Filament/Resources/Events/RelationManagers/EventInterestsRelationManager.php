<?php

namespace App\Filament\Resources\Events\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventInterestsRelationManager extends RelationManager
{
    protected static string $relationship = 'eventInterests';

    protected static ?string $title = 'المهتمون';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table->recordTitleAttribute('id')->columns([
            TextColumn::make('customer.name')->label('العميل')->searchable(),
            TextColumn::make('customer.phone_number')->label('الهاتف')->searchable(),
            TextColumn::make('status')->label('الحالة')->badge(),
            TextColumn::make('source')->label('المصدر')->badge(),
            TextColumn::make('preferred_contact_channel')->label('قناة التواصل')->placeholder('-'),
            TextColumn::make('last_expressed_at')->label('آخر اهتمام')->dateTime()->sortable(),
        ]);
    }
}
