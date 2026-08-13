<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Models\Event;
use App\Models\EventCancellation;
use App\Support\MoneyFormatter;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class EventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make(__('admin.resources.events.label'))
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('admin.event_infolist.tabs.overview'))
                            ->schema([
                                Section::make(__('admin.event_infolist.sections.identity'))
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label(__('admin.fields.name')),
                                        TextEntry::make('slug')
                                            ->label(__('admin.fields.slug'))
                                            ->copyable(),
                                        TextEntry::make('status')
                                            ->label(__('admin.fields.status'))
                                            ->badge(),
                                        TextEntry::make('enrollment_status')->label('حالة التسجيل')->badge(),
                                        TextEntry::make('type')
                                            ->label(__('admin.fields.type'))
                                            ->badge(),
                                        TextEntry::make('location')
                                            ->label(__('admin.fields.location'))
                                            ->placeholder('-'),
                                        TextEntry::make('created_at')
                                            ->label(__('admin.fields.created_at'))
                                            ->dateTime(),
                                    ]),
                            ]),
                        Tab::make(__('admin.event_infolist.tabs.capacity_pricing'))
                            ->schema([
                                Section::make(__('admin.event_infolist.sections.capacity_pricing'))
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('seat_capacity')
                                            ->label(__('admin.fields.seat_capacity')),
                                        TextEntry::make('remaining_seats')
                                            ->label(__('admin.event_infolist.fields.remaining_seats'))
                                            ->state(fn (Event $record): int => $record->remainingSeats()),
                                        TextEntry::make('price_baisa')
                                            ->label(__('admin.fields.price'))
                                            ->state(fn (Event $record): string => MoneyFormatter::baisa($record->price_baisa, $record->currency)),
                                        TextEntry::make('currency')
                                            ->label(__('admin.fields.currency')),
                                        TextEntry::make('minimum_age')
                                            ->label(__('admin.fields.minimum_age')),
                                        TextEntry::make('maximum_age')
                                            ->label(__('admin.fields.maximum_age')),
                                        TextEntry::make('starts_at')
                                            ->label(__('admin.fields.starts_at'))
                                            ->dateTime()
                                            ->placeholder('-'),
                                        TextEntry::make('ends_at')
                                            ->label(__('admin.fields.ends_at'))
                                            ->dateTime()
                                            ->placeholder('-'),
                                    ]),
                            ]),
                        Tab::make(__('admin.event_infolist.tabs.public_content'))
                            ->schema([
                                Section::make(__('admin.event_infolist.sections.public_content'))
                                    ->schema([
                                        TextEntry::make('subtitle')
                                            ->label('العنوان الفرعي للبطاقة')
                                            ->placeholder('-'),
                                        TextEntry::make('card_topics')
                                            ->label('وسوم البطاقة')
                                            ->listWithLineBreaks()
                                            ->bulleted()
                                            ->placeholder('-'),
                                        TextEntry::make('schedule_text')
                                            ->label('نص الموعد والمدة للبطاقة')
                                            ->placeholder('-')
                                            ->columnSpanFull(),
                                        TextEntry::make('excerpt')
                                            ->label(__('admin.fields.excerpt'))
                                            ->placeholder('-')
                                            ->columnSpanFull(),
                                        TextEntry::make('description_html')
                                            ->label(__('admin.fields.description'))
                                            ->html()
                                            ->placeholder('-')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make(__('admin.event_infolist.tabs.contract_participants'))
                            ->schema([
                                Section::make(__('admin.event_infolist.sections.contract'))
                                    ->schema([
                                        TextEntry::make('contract_terms_html')
                                            ->label(__('admin.fields.contract_terms'))
                                            ->html()
                                            ->placeholder('-')
                                            ->columnSpanFull(),
                                    ]),
                                Section::make(__('admin.event_infolist.sections.participant_fields'))
                                    ->schema([
                                        TextEntry::make('participant_extra_fields')
                                            ->label(__('admin.participant_extra_fields.heading'))
                                            ->state(fn (Event $record): array => self::participantFieldSummary($record))
                                            ->listWithLineBreaks()
                                            ->bulleted()
                                            ->placeholder('-')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('الإلغاء والاسترداد')
                            ->visible(fn (Event $record): bool => $record->cancellation()->exists())
                            ->schema([
                                Section::make('حالة معالجة الإلغاء')
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('cancellation.status')->label('الحالة')->badge(),
                                        TextEntry::make('cancellation.reason')->label('السبب')->columnSpanFull(),
                                        TextEntry::make('cancellation.bookings_count')->label('الحجوزات'),
                                        TextEntry::make('cancellation.payments_count')->label('المدفوعات'),
                                        TextEntry::make('cancellation.refundable_amount_baisa')
                                            ->label('المبلغ المطلوب رده')
                                            ->state(fn (Event $record): string => self::cancellationMoney($record, 'refundable_amount_baisa')),
                                        TextEntry::make('cancellation.refunded_payments_count')->label('المدفوعات المستردة'),
                                        TextEntry::make('cancellation.refunded_amount_baisa')
                                            ->label('المبلغ المسترد')
                                            ->state(fn (Event $record): string => self::cancellationMoney($record, 'refunded_amount_baisa')),
                                        TextEntry::make('cancellation.errors')
                                            ->label('أخطاء تحتاج متابعة')
                                            ->state(fn (Event $record): array => self::cancellationErrorSummary($record))
                                            ->listWithLineBreaks()
                                            ->bulleted()
                                            ->columnSpanFull()
                                            ->placeholder('-'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function participantFieldSummary(Event $event): array
    {
        return collect($event->participant_extra_fields ?? [])
            ->map(function (array $field): string {
                $label = (string) ($field['label'] ?? $field['key'] ?? '-');
                $type = (string) ($field['type'] ?? 'text');
                $required = ($field['required'] ?? false) ? __('admin.participant_extra_fields.required') : __('admin.event_infolist.fields.optional');

                return "{$label} ({$type}, {$required})";
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function cancellationErrorSummary(Event $event): array
    {
        $cancellation = EventCancellation::query()->where('event_id', $event->id)->first();
        $errors = $cancellation instanceof EventCancellation ? ($cancellation->errors ?? []) : [];

        return collect($errors)
            ->map(function (array $error): string {
                $message = (string) ($error['message'] ?? 'خطأ غير معروف');
                $context = collect([
                    isset($error['booking_id']) ? 'الحجز: '.$error['booking_id'] : null,
                    isset($error['affiliate_commission_id']) ? 'العمولة: '.$error['affiliate_commission_id'] : null,
                ])->filter()->implode('، ');

                return $context === '' ? $message : "{$message} ({$context})";
            })
            ->values()
            ->all();
    }

    private static function cancellationMoney(Event $event, string $amountField): string
    {
        $cancellation = EventCancellation::query()->where('event_id', $event->id)->first();

        if (! $cancellation instanceof EventCancellation) {
            return '-';
        }

        return MoneyFormatter::baisa((int) $cancellation->getAttribute($amountField), $cancellation->currency);
    }
}
