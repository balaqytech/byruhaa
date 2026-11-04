<?php

namespace App\Modules\Store\Filament\Resources\Orders\Tables;

use App\Modules\Store\Enums\OrderStatus;
use App\Modules\Store\Models\Order;
use App\Modules\Store\States\Order\OrderState;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->latest('id'))
            ->columns([
                TextColumn::make('reference')->label(__('admin.fields.reference'))->searchable()->sortable()->copyable(),
                TextColumn::make('customer_name')
                    ->label(__('admin.fields.customer'))
                    ->description(fn (Order $record): ?string => $record->customer_id === null ? 'ضيف' : null)
                    ->searchable(),
                TextColumn::make('customer_phone')->label(__('admin.fields.phone_number'))->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->formatStateUsing(fn (OrderState $state): string => __('admin.store.order_statuses.'.$state->getValue()))
                    ->badge()
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label(__('admin.store.admin_order.payment_method'))
                    ->formatStateUsing(fn (string $state): string => $state === 'wallet'
                        ? __('admin.store.admin_order.minor_wallet')
                        : ($state === 'cash' ? 'نقدًا' : __('admin.store.admin_order.direct_payment')))
                    ->badge(),
                TextColumn::make('pickup_type')
                    ->label(__('admin.store.admin_order.pickup_type'))
                    ->formatStateUsing(fn (string $state): string => __('admin.store.pickup_types.'.$state))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pickup_at')->label(__('admin.fields.pickup_at'))->dateTime()->sortable()->placeholder(__('admin.store.pickup_types.immediate')),
                TextColumn::make('total_baisa')->label(__('admin.fields.total'))->money('OMR', divideBy: 1000)->sortable(),
                TextColumn::make('created_at')->label(__('admin.fields.created_at'))->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.fields.status'))
                    ->options(fn (): array => collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $status): array => [$status->value => __('admin.store.order_statuses.'.$status->value)])->all()),
                SelectFilter::make('payment_method')
                    ->label(__('admin.store.admin_order.payment_method'))
                    ->options([
                        'thawani' => __('admin.store.admin_order.direct_payment'),
                        'wallet' => __('admin.store.admin_order.minor_wallet'),
                        'cash' => 'نقدًا',
                    ]),
                SelectFilter::make('pickup_type')
                    ->label(__('admin.store.admin_order.pickup_type'))
                    ->options([
                        'immediate' => __('admin.store.pickup_types.immediate'),
                        'scheduled' => __('admin.store.pickup_types.scheduled'),
                    ]),
                Filter::make('pickup_date')->label(__('admin.filters.pickup_date'))->form([
                    DatePicker::make('date')->label(__('admin.fields.pickup_date')),
                ])->query(fn (Builder $query, array $data): Builder => filled($data['date'] ?? null) ? $query->whereDate('pickup_at', $data['date']) : $query),
            ])
            ->recordActions([ViewAction::make()])
            ->toolbarActions([]);
    }
}
