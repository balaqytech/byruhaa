<?php

namespace App\Filament\Resources\PaymentRefunds\Tables;

use App\Actions\ConfirmManualPaymentRefund;
use App\Enums\PaymentRefundState;
use App\Filament\Resources\Payments\PaymentResource;
use App\Modules\Finance\Models\PaymentRefund;
use App\Modules\Identity\Models\User;
use App\Support\MoneyFormatter;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PaymentRefundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with([
                    'payment.bookingInstallment.paymentSchedule.booking.customer',
                    'ledgerTransaction',
                ])
                ->latest('id'))
            ->columns([
                TextColumn::make('reference')
                    ->label(__('admin.fields.reference'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('payment.reference')
                    ->label(__('admin.resources.payments.label'))
                    ->searchable()
                    ->url(fn (PaymentRefund $record): string => PaymentResource::getUrl('view', ['record' => $record->payment])),
                TextColumn::make('payment.bookingInstallment.paymentSchedule.booking.reference')
                    ->label(__('admin.fields.booking'))
                    ->searchable(),
                TextColumn::make('payment.bookingInstallment.paymentSchedule.booking.customer.name')
                    ->label(__('admin.fields.customer'))
                    ->searchable(),
                TextColumn::make('amount_baisa')
                    ->label(__('admin.fields.amount'))
                    ->formatStateUsing(fn (int $state, PaymentRefund $record): string => MoneyFormatter::baisa($state, $record->currency))
                    ->sortable(),
                TextColumn::make('state')
                    ->label(__('admin.fields.state'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('reason')
                    ->label(__('admin.fields.reason'))
                    ->searchable()
                    ->limit(40),
                TextColumn::make('provider_status')
                    ->label(__('admin.fields.provider_status'))
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('processed_at')
                    ->label(__('admin.fields.processed_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->label(__('admin.fields.state'))
                    ->options(PaymentRefundState::class),
            ])
            ->recordActions([
                self::confirmManualRefundAction(),
                ViewAction::make(),
            ]);
    }

    public static function confirmManualRefundAction(): Action
    {
        return Action::make('confirm_manual_refund')
            ->label('تأكيد الاسترداد اليدوي')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (PaymentRefund $record): bool => $record->state === PaymentRefundState::ManualRequired)
            ->modalHeading('تأكيد وصول الاسترداد للعميل')
            ->modalDescription('استخدم هذا الإجراء فقط بعد تأكيد Thawani تنفيذ الاسترداد. سيُنشأ القيد المحاسبي ويُبلّغ العميل.')
            ->schema([
                TextInput::make('manual_reference')->label('مرجع الاسترداد')->required()->maxLength(255),
                DateTimePicker::make('completed_at')->label('تاريخ إتمام الاسترداد')->required()->default(now()),
                Textarea::make('manual_notes')->label('ملاحظات')->maxLength(2000),
                FileUpload::make('manual_evidence_path')
                    ->label('مستند الإثبات')
                    ->disk('local')
                    ->directory('refund-evidence')
                    ->visibility('private')
                    ->maxSize(5120),
            ])
            ->requiresConfirmation()
            ->action(function (PaymentRefund $record, ConfirmManualPaymentRefund $confirmManualPaymentRefund, array $data): void {
                $user = auth('web')->user();
                if (! $user instanceof User) {
                    abort(403);
                }

                $confirmManualPaymentRefund->execute(
                    $record,
                    (string) $data['manual_reference'],
                    Carbon::parse($data['completed_at']),
                    $user->id,
                    filled($data['manual_notes'] ?? null) ? (string) $data['manual_notes'] : null,
                    filled($data['manual_evidence_path'] ?? null) ? (string) $data['manual_evidence_path'] : null,
                );

                Notification::make()->title('تم تسجيل الاسترداد اليدوي وإبلاغ العميل')->success()->send();
            });
    }
}
