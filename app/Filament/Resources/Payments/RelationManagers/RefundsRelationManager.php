<?php

namespace App\Filament\Resources\Payments\RelationManagers;

use App\Filament\Resources\PaymentRefunds\PaymentRefundResource;
use App\Models\PaymentRefund;
use App\Support\MoneyFormatter;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RefundsRelationManager extends RelationManager
{
    protected static string $relationship = 'refunds';

    protected static ?string $title = 'Refunds';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')
                    ->label(__('admin.fields.reference'))
                    ->searchable()
                    ->url(fn (PaymentRefund $record): string => PaymentRefundResource::getUrl('view', ['record' => $record])),
                TextColumn::make('amount_baisa')
                    ->label(__('admin.fields.amount'))
                    ->formatStateUsing(fn (int $state, PaymentRefund $record): string => MoneyFormatter::baisa($state, $record->currency)),
                TextColumn::make('state')
                    ->label(__('admin.fields.state'))
                    ->badge(),
                TextColumn::make('reason')
                    ->label(__('admin.fields.reason'))
                    ->limit(50)
                    ->placeholder('-'),
                TextColumn::make('processed_at')
                    ->label(__('admin.fields.processed_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
