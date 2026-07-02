<?php

namespace App\Filament\Resources\Bookings;

use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Bookings\Pages\ViewBooking;
use App\Filament\Resources\Bookings\RelationManagers\FamilyMembersRelationManager;
use App\Filament\Resources\Bookings\RelationManagers\InstallmentsRelationManager;
use App\Filament\Resources\Bookings\Schemas\BookingForm;
use App\Filament\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking;
use App\Support\MoneyFormatter;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return BookingForm::configure($schema);
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.bookings.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.bookings.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.bookings.navigation_label');
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make(__('admin.resources.bookings.label'))
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('admin.resources.bookings.label'))
                            ->columns(1)
                            ->schema([
                                Section::make(__('admin.resources.bookings.label'))
                                    ->columnSpanFull()
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('reference')
                                            ->label(__('admin.fields.reference'))
                                            ->copyable(),
                                        TextEntry::make('state')
                                            ->label(__('admin.fields.state'))
                                            ->badge(),
                                        TextEntry::make('created_at')
                                            ->label(__('admin.fields.created_at'))
                                            ->dateTime(),
                                        TextEntry::make('reviewer.name')
                                            ->label(__('admin.fields.reviewer'))
                                            ->placeholder('-'),
                                        TextEntry::make('reviewed_at')
                                            ->label(__('admin.fields.reviewed_at'))
                                            ->dateTime()
                                            ->placeholder('-'),
                                        TextEntry::make('review_notes')
                                            ->label(__('admin.fields.review_notes'))
                                            ->columnSpanFull()
                                            ->placeholder('-'),
                                    ]),

                                Section::make(__('ui.payments.heading'))
                                    ->columnSpanFull()
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('unit_price_baisa')
                                            ->label(__('admin.fields.price'))
                                            ->state(fn (Booking $record): string => self::money($record->unit_price_baisa, $record->currency)),
                                        TextEntry::make('family_member_count')
                                            ->label(__('admin.fields.family_members')),
                                        TextEntry::make('subtotal_baisa')
                                            ->label(__('ui.payments.subtotal'))
                                            ->state(fn (Booking $record): string => self::money($record->subtotal_baisa, $record->currency)),
                                        TextEntry::make('discount_name')
                                            ->label(__('admin.fields.discount'))
                                            ->placeholder('-'),
                                        TextEntry::make('coupon_code')
                                            ->label(__('admin.fields.coupon_code'))
                                            ->placeholder('-'),
                                        TextEntry::make('discount_amount_baisa')
                                            ->label(__('admin.fields.discount_amount'))
                                            ->state(fn (Booking $record): string => self::money($record->discount_amount_baisa, $record->currency)),
                                        TextEntry::make('total_baisa')
                                            ->label(__('ui.payments.total'))
                                            ->state(fn (Booking $record): string => self::money($record->total_baisa, $record->currency)),
                                    ]),
                            ]),

                        Tab::make(__('admin.fields.customer'))
                            ->columns(1)
                            ->schema([
                                Section::make(__('admin.fields.customer'))
                                    ->columnSpanFull()
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('customer.name')
                                            ->label(__('admin.fields.name')),
                                        TextEntry::make('customer.email')
                                            ->label(__('admin.fields.email_address'))
                                            ->placeholder('-'),
                                        TextEntry::make('customer.phone_number')
                                            ->label(__('admin.fields.phone_number'))
                                            ->placeholder('-'),
                                    ]),

                                Section::make(__('admin.fields.event'))
                                    ->columnSpanFull()
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('event.name')
                                            ->label(__('admin.fields.name')),
                                        TextEntry::make('event.location')
                                            ->label(__('admin.fields.location'))
                                            ->placeholder('-'),
                                        TextEntry::make('event.type')
                                            ->label(__('admin.fields.type'))
                                            ->badge(),
                                        TextEntry::make('event.starts_at')
                                            ->label(__('admin.fields.starts_at'))
                                            ->dateTime()
                                            ->placeholder('-'),
                                        TextEntry::make('event.ends_at')
                                            ->label(__('admin.fields.ends_at'))
                                            ->dateTime()
                                            ->placeholder('-'),
                                    ]),
                            ]),
                        Tab::make(__('ui.payments.heading'))
                            ->columns(1)
                            ->schema([
                                Section::make(__('ui.payments.plan'))
                                    ->columnSpanFull()
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('paymentSchedule.plan_name')
                                            ->label(__('ui.payments.plan'))
                                            ->placeholder('-'),
                                        TextEntry::make('paymentSchedule.total_baisa')
                                            ->label(__('ui.payments.total'))
                                            ->state(fn (Booking $record): string => $record->paymentSchedule
                                                ? self::money($record->paymentSchedule->total_baisa, $record->paymentSchedule->currency)
                                                : '-'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            FamilyMembersRelationManager::class,
            InstallmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'create' => CreateBooking::route('/create'),
            'view' => ViewBooking::route('/{record}'),
            'edit' => EditBooking::route('/{record}/edit'),
        ];
    }

    private static function money(?int $amountBaisa, ?string $currency): string
    {
        return MoneyFormatter::baisa((int) $amountBaisa, $currency ?: 'OMR');
    }
}
