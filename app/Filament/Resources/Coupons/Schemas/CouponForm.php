<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Support\Money\MoneyFactory;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('admin.fields.coupon_code'))
                    ->required()
                    ->maxLength(255)
                    ->formatStateUsing(fn (mixed $state): ?string => blank($state) ? null : Coupon::normalizeCode((string) $state))
                    ->dehydrateStateUsing(fn (mixed $state): ?string => blank($state) ? null : Coupon::normalizeCode((string) $state))
                    ->rules([
                        fn (?Coupon $record): Closure => self::uniqueNormalizedCodeRule($record),
                    ]),
                TextInput::make('name')
                    ->label(__('admin.fields.name'))
                    ->required()
                    ->maxLength(255),
                Select::make('event_id')
                    ->label(__('admin.fields.event'))
                    ->relationship('event', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('type')
                    ->label(__('admin.fields.type'))
                    ->required()
                    ->options(CouponType::class)
                    ->default(CouponType::FixedAmountPerMember->value)
                    ->live(),
                TextInput::make('amount')
                    ->label(__('admin.fields.discount_amount_per_member'))
                    ->required(fn (Get $get): bool => self::isType($get('type'), CouponType::FixedAmountPerMember))
                    ->rules(['nullable', 'regex:/^\d+(\.\d{1,3})?$/'])
                    ->formatStateUsing(fn (mixed $state): ?string => self::moneyInputState($state))
                    ->suffix('OMR')
                    ->visible(fn (Get $get): bool => self::isType($get('type'), CouponType::FixedAmountPerMember)),
                TextInput::make('percentage_basis_points')
                    ->label(__('admin.fields.discount_percentage'))
                    ->required(fn (Get $get): bool => self::isType($get('type'), CouponType::PercentagePerMember))
                    ->rules(['nullable', 'regex:/^\d+(\.\d{1,2})?$/', 'numeric', 'min:0.01', 'max:100'])
                    ->formatStateUsing(fn (mixed $state): ?string => self::percentageInputState($state))
                    ->dehydrateStateUsing(fn (mixed $state): ?int => self::percentageBasisPoints($state))
                    ->suffix('%')
                    ->visible(fn (Get $get): bool => self::isType($get('type'), CouponType::PercentagePerMember)),
                TextInput::make('currency')
                    ->label(__('admin.fields.currency'))
                    ->required()
                    ->default('OMR')
                    ->maxLength(3),
                DateTimePicker::make('expires_at')
                    ->label(__('admin.fields.expires_at'))
                    ->required(),
                TextInput::make('minimum_family_members')
                    ->label(__('admin.fields.minimum_family_members'))
                    ->required()
                    ->numeric()
                    ->rules(['integer'])
                    ->minValue(1)
                    ->default(1),
                TextInput::make('maximum_family_members')
                    ->label(__('admin.fields.maximum_family_members'))
                    ->numeric()
                    ->minValue(1)
                    ->rules([
                        'nullable',
                        'integer',
                        fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            $minimumFamilyMembers = $get('minimum_family_members');

                            if (blank($value) || blank($minimumFamilyMembers)) {
                                return;
                            }

                            if ((int) $value < (int) $minimumFamilyMembers) {
                                $fail(__('validation.gte.numeric', [
                                    'attribute' => __('admin.fields.maximum_family_members'),
                                    'value' => __('admin.fields.minimum_family_members'),
                                ]));
                            }
                        },
                    ]),
                Toggle::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->default(true),
            ]);
    }

    private static function moneyInputState(mixed $state): ?string
    {
        if ($state instanceof Money) {
            return MoneyFactory::formatMoneyAmount($state);
        }

        return blank($state) ? null : (string) $state;
    }

    private static function isType(mixed $state, CouponType $type): bool
    {
        return $state === $type || $state === $type->value;
    }

    private static function uniqueNormalizedCodeRule(?Coupon $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
            if (! is_string($value) || blank($value)) {
                return;
            }

            $query = Coupon::query()->matchingCode($value);

            if ($record instanceof Coupon) {
                $query->whereKeyNot($record->getKey());
            }

            if ($query->exists()) {
                $fail(__('validation.unique', ['attribute' => __('admin.fields.coupon_code')]));
            }
        };
    }

    private static function percentageInputState(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        return number_format(((int) $state) / 100, 2, '.', '');
    }

    private static function percentageBasisPoints(mixed $state): ?int
    {
        if (blank($state)) {
            return null;
        }

        return BigDecimal::of((string) $state)
            ->multipliedBy(100)
            ->toScale(0, RoundingMode::UNNECESSARY)
            ->toInt();
    }
}
