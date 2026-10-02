<?php

namespace App\Modules\Store\Filament\Resources\Orders\Schemas;

use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Settings\StoreSettings;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make(self::steps())
                ->columnSpanFull(),
        ]);
    }

    /** @return array<Step> */
    public static function steps(): array
    {
        return [
            Step::make(__('admin.store.order_tabs.customer_payment'))
                ->schema([
                    Section::make(__('admin.store.sections.customer_payment'))
                        ->columns(2)
                        ->schema([
                            Select::make('customer_id')
                                ->label(__('admin.fields.customer'))
                                ->searchable()
                                ->getSearchResultsUsing(fn (string $search): array => Customer::query()
                                    ->where(fn (Builder $query): Builder => $query
                                        ->where('name', 'like', "%{$search}%")
                                        ->orWhere('phone_number', 'like', "%{$search}%"))
                                    ->orderBy('name')
                                    ->limit(50)
                                    ->get(['id', 'name', 'phone_number'])
                                    ->mapWithKeys(fn (Customer $customer): array => [$customer->id => self::customerLabel($customer)])
                                    ->all())
                                ->getOptionLabelUsing(fn (mixed $value): ?string => ($customer = Customer::query()->find((int) $value)) instanceof Customer
                                    ? self::customerLabel($customer)
                                    : null)
                                ->live()
                                ->afterStateUpdated(function (callable $set): void {
                                    $set('minor_profile_id', null);
                                    $set('payment_method', 'thawani');
                                })
                                ->required(),
                            Select::make('minor_profile_id')
                                ->label(__('admin.store.admin_order.minor_profile'))
                                ->helperText(__('admin.store.admin_order.minor_optional_help'))
                                ->options(fn (callable $get): array => filled($get('customer_id'))
                                    ? MinorProfile::query()
                                        ->with('familyMember:id,name')
                                        ->where('status', MinorProfileStatus::Active->value)
                                        ->whereHas('familyMember', fn (Builder $query): Builder => $query->where('customer_id', (int) $get('customer_id')))
                                        ->orderBy('id')
                                        ->get(['id', 'family_member_id', 'member_code'])
                                        ->mapWithKeys(fn (MinorProfile $profile): array => [$profile->id => $profile->familyMember->name.' ('.$profile->member_code.')'])
                                        ->all()
                                    : [])
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(fn (mixed $state, callable $set) => blank($state) ? $set('payment_method', 'thawani') : null),
                            Select::make('payment_method')
                                ->label(__('admin.store.admin_order.payment_method'))
                                ->helperText(fn (callable $get): ?string => $get('payment_method') === 'thawani'
                                    ? __('admin.store.admin_order.payment_window', ['minutes' => max(1, app(StoreSettings::class)->reservation_duration_minutes)])
                                    : null)
                                ->options(fn (callable $get): array => filled($get('minor_profile_id'))
                                    ? ['thawani' => __('admin.store.admin_order.direct_payment'), 'wallet' => __('admin.store.admin_order.minor_wallet')]
                                    : ['thawani' => __('admin.store.admin_order.direct_payment')])
                                ->default('thawani')
                                ->live()
                                ->required(),
                        ]),
                ]),
            Step::make(__('admin.store.sections.items'))
                ->schema([
                    Section::make(__('admin.store.sections.items'))
                        ->schema([
                            Repeater::make('items')
                                ->label(__('admin.store.sections.items'))
                                ->schema([
                                    Select::make('product_option_id')
                                        ->label(__('admin.fields.product'))
                                        ->searchable()
                                        ->getSearchResultsUsing(fn (string $search): array => ProductOption::query()
                                            ->with('product:id,name')
                                            ->where('is_available', true)
                                            ->whereHas('product', fn (Builder $query): Builder => $query
                                                ->where('status', 'active')
                                                ->whereHas('category', fn (Builder $category): Builder => $category->where('is_active', true)))
                                            ->where(fn (Builder $query): Builder => $query
                                                ->where('name', 'like', "%{$search}%")
                                                ->orWhere('sku', 'like', "%{$search}%")
                                                ->orWhereHas('product', fn (Builder $product): Builder => $product->where('name', 'like', "%{$search}%")))
                                            ->orderBy('id')
                                            ->limit(50)
                                            ->get(['id', 'product_id', 'name', 'sku'])
                                            ->mapWithKeys(fn (ProductOption $option): array => [$option->id => self::optionLabel($option)])
                                            ->all())
                                        ->getOptionLabelUsing(fn (mixed $value): ?string => ($option = ProductOption::query()->with('product:id,name')->find((int) $value)) instanceof ProductOption
                                            ? self::optionLabel($option)
                                            : null)
                                        ->required(),
                                    TextInput::make('quantity')
                                        ->label(__('admin.fields.quantity'))
                                        ->numeric()
                                        ->minValue(1)
                                        ->maxValue(99)
                                        ->default(1)
                                        ->required(),
                                ])
                                ->columns(2)
                                ->defaultItems(1)
                                ->minItems(1)
                                ->maxItems(50)
                                ->required(),
                        ]),
                ]),
            Step::make(__('admin.store.order_tabs.pickup_notes'))
                ->schema([
                    Section::make(__('admin.store.sections.receipt_pickup'))
                        ->columns(2)
                        ->schema([
                            Select::make('pickup_type')
                                ->label(__('admin.store.admin_order.pickup_type'))
                                ->options([
                                    'immediate' => __('admin.store.pickup_types.immediate'),
                                    'scheduled' => __('admin.store.pickup_types.scheduled'),
                                ])
                                ->default('immediate')
                                ->live()
                                ->required(),
                            DateTimePicker::make('pickup_at')
                                ->label(__('admin.fields.pickup_at'))
                                ->seconds(false)
                                ->visible(fn (callable $get): bool => $get('pickup_type') === 'scheduled')
                                ->required(fn (callable $get): bool => $get('pickup_type') === 'scheduled'),
                            Textarea::make('note')
                                ->label(__('admin.fields.note'))
                                ->maxLength(5000)
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }

    private static function customerLabel(Customer $customer): string
    {
        return $customer->name.' ('.$customer->phone_number.')';
    }

    private static function optionLabel(ProductOption $option): string
    {
        return $option->product->name.' — '.$option->name.' ('.$option->sku.')';
    }
}
