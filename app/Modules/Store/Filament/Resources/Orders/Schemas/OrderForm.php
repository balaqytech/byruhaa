<?php

namespace App\Modules\Store\Filament\Resources\Orders\Schemas;

use App\Filament\Resources\Customers\CustomerResource;
use App\Modules\Identity\Actions\CreateMinorProfile;
use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\PhoneNumberNormalizer;
use App\Modules\Store\Filament\Resources\Orders\Pages\CreateOrder;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Settings\StoreSettings;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->label(__('admin.fields.name'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('phone_number')
                                        ->label(__('admin.fields.phone_number'))
                                        ->tel()
                                        ->required()
                                        ->rules(['phone:INTERNATIONAL,OM'])
                                        ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                            $normalizedPhone = app(PhoneNumberNormalizer::class)->normalize((string) $value);

                                            if (Customer::query()->where('phone_number', $normalizedPhone)->exists()) {
                                                $fail(__('validation.unique', ['attribute' => __('admin.fields.phone_number')]));
                                            }
                                        }),
                                    TextInput::make('email')
                                        ->label(__('admin.fields.email_address'))
                                        ->email()
                                        ->maxLength(255),
                                    TextInput::make('password')
                                        ->label(__('admin.fields.password'))
                                        ->password()
                                        ->revealable()
                                        ->required()
                                        ->rule(Password::defaults()),
                                ])
                                ->createOptionAction(fn (Action $action): Action => $action
                                    ->label(__('admin.store.admin_order.quick_create_customer'))
                                    ->modalHeading(__('admin.store.admin_order.quick_create_customer'))
                                    ->visible(fn (): bool => CustomerResource::canCreate()))
                                ->createOptionUsing(function (array $data): int {
                                    abort_unless(CustomerResource::canCreate(), 403);

                                    $data['phone_number'] = app(PhoneNumberNormalizer::class)->normalize($data['phone_number']);
                                    $data['email'] = blank($data['email'] ?? null) ? null : $data['email'];

                                    Validator::make($data, [
                                        'phone_number' => ['required', 'phone:INTERNATIONAL,OM', Rule::unique(Customer::class)],
                                        'email' => ['nullable', 'email', Rule::unique(Customer::class)],
                                    ])->validate();

                                    return Customer::query()->create([
                                        'name' => $data['name'],
                                        'phone_number' => $data['phone_number'],
                                        'email' => $data['email'],
                                        'password' => $data['password'],
                                    ])->getKey();
                                })
                                ->required(),
                            Select::make('minor_profile_id')
                                ->label(__('admin.store.admin_order.minor_profile'))
                                ->helperText(__('admin.store.admin_order.minor_optional_help'))
                                ->hintAction(Action::make('quickCreateMinor')
                                    ->label(__('admin.store.admin_order.quick_create_minor'))
                                    ->visible(fn (callable $get): bool => filled($get('customer_id')) && self::canCreateMinor())
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('admin.fields.name'))
                                            ->required()
                                            ->maxLength(255),
                                        DatePicker::make('birth_date')
                                            ->label(__('admin.store.admin_order.birth_date'))
                                            ->required()
                                            ->rules(['before:today']),
                                        TextInput::make('school_name')
                                            ->label(__('admin.store.admin_order.school_name'))
                                            ->maxLength(255),
                                        TextInput::make('grade')
                                            ->label(__('admin.store.admin_order.grade'))
                                            ->maxLength(64),
                                        Checkbox::make('guardian_consent_confirmed')
                                            ->label(__('admin.store.admin_order.guardian_consent_confirmed'))
                                            ->accepted()
                                            ->required(),
                                    ])
                                    ->action(function (array $data, CreateOrder $livewire): void {
                                        abort_unless(self::canCreateMinor(), 403);

                                        $guardian = Customer::query()->findOrFail((int) ($livewire->data['customer_id'] ?? 0));
                                        $result = app(CreateMinorProfile::class)->execute($guardian, $data, request()->ip());
                                        $profile = $result['profile'];
                                        $activationUrl = URL::temporarySignedRoute('minor.activate', $profile->activation_token_expires_at, [
                                            'minorProfile' => $profile->id,
                                            'token' => $result['activation_token'],
                                        ]);

                                        Notification::make()
                                            ->title(__('admin.store.admin_order.minor_created_pending'))
                                            ->body(__('admin.store.admin_order.activation_link').': '.$activationUrl)
                                            ->success()
                                            ->persistent()
                                            ->send();
                                    }))
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
                                    Textarea::make('note')
                                        ->label(__('admin.fields.note'))
                                        ->maxLength(500)
                                        ->rows(2)
                                        ->columnSpanFull(),
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

    /** @return array<Step> */
    public static function guestSteps(): array
    {
        $steps = self::steps();

        $steps[0] = Step::make(__('admin.store.admin_order.guest_details'))
            ->schema([
                Section::make(__('admin.store.admin_order.guest_details'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('customer_name')
                            ->label(__('admin.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('customer_phone')
                            ->label(__('admin.fields.phone_number'))
                            ->tel()
                            ->required()
                            ->rules(['phone:INTERNATIONAL,OM'])
                            ->maxLength(32),
                        TextInput::make('customer_email')
                            ->label(__('admin.fields.email_address'))
                            ->email()
                            ->maxLength(255),
                    ]),
            ]);

        return $steps;
    }

    private static function customerLabel(Customer $customer): string
    {
        return $customer->name.' ('.$customer->phone_number.')';
    }

    private static function canCreateMinor(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && ($user->isPanelAdministrator() || $user->can('Create:MinorProfile'));
    }

    private static function optionLabel(ProductOption $option): string
    {
        return $option->product->name.' — '.$option->name.' ('.$option->sku.')';
    }
}
