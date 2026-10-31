<?php

namespace App\Filament\Resources\OrderRefunds;

use App\Filament\Resources\OrderRefunds\Pages\ListOrderRefunds;
use App\Filament\Resources\ShieldResource;
use App\Modules\Identity\Models\User;
use App\Modules\Store\Actions\ManageOrderRefund;
use App\Modules\Store\Models\OrderRefundRequest;
use App\Support\MoneyFormatter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class OrderRefundResource extends ShieldResource
{
    protected static ?string $model = OrderRefundRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptRefund;

    public static function getModelLabel(): string
    {
        return 'طلب استرداد للقهوة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'طلبات استرداد القهوة';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.finance');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canManage(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && ($user->isPanelAdministrator() || $user->can('Update:OrderRefundRequest'));
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with('order')->latest('id'))
            ->columns([
                TextColumn::make('reference')->label('المرجع')->searchable()->copyable(),
                TextColumn::make('order.reference')->label('الطلب')->searchable(),
                TextColumn::make('order.customer_name')->label('العميل'),
                TextColumn::make('order.payment_method')->label('وسيلة الدفع'),
                TextColumn::make('amount_baisa')->label('المبلغ')->formatStateUsing(fn (int $state, OrderRefundRequest $record): string => MoneyFormatter::baisa($state, $record->currency)),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('reason')->label('السبب')->limit(40),
                TextColumn::make('payout_reference')->label('مرجع الرد')->placeholder('-'),
                TextColumn::make('created_at')->label('تاريخ الطلب')->dateTime()->sortable(),
            ])->recordActions([
                Action::make('approve')->label('موافقة وتنفيذ')->visible(fn (OrderRefundRequest $record): bool => static::canManage() && $record->status === 'pending')
                    ->schema([Textarea::make('note')->label('ملاحظة المراجعة')])
                    ->requiresConfirmation()
                    ->action(function (OrderRefundRequest $record, array $data, ManageOrderRefund $refunds): void {
                        abort_unless(static::canManage(), 403);
                        $refunds->approve($record, (int) Filament::auth()->id(), $data['note'] ?? null);
                        Notification::make()->title('تمت معالجة الموافقة')->success()->send();
                    }),
                Action::make('reject')->label('رفض')->color('danger')->visible(fn (OrderRefundRequest $record): bool => static::canManage() && $record->status === 'pending')
                    ->schema([Textarea::make('reason')->label('سبب الرفض')->required()])
                    ->action(function (OrderRefundRequest $record, array $data, ManageOrderRefund $refunds): void {
                        abort_unless(static::canManage(), 403);
                        $refunds->reject($record, (int) Filament::auth()->id(), (string) $data['reason']);
                    }),
                Action::make('cash_paid')->label('تأكيد تسليم النقد')->visible(fn (OrderRefundRequest $record): bool => static::canManage() && $record->status === 'approved' && $record->order->payment_method === 'cash')
                    ->schema([TextInput::make('receipt_reference')->label('مرجع إيصال التسليم')->required()->maxLength(255)])
                    ->requiresConfirmation()
                    ->action(function (OrderRefundRequest $record, array $data, ManageOrderRefund $refunds): void {
                        abort_unless(static::canManage(), 403);
                        $refunds->completeCash($record, (int) Filament::auth()->id(), (string) $data['receipt_reference']);
                        Notification::make()->title('تم إثبات رد النقد والقيد')->success()->send();
                    }),
                Action::make('confirm_provider_refund')->label('تأكيد الاسترداد من Thawani')->visible(fn (OrderRefundRequest $record): bool => static::canManage() && $record->status === 'manual_review' && $record->order->payment_method === 'thawani')
                    ->schema([
                        TextInput::make('provider_refund_reference')->label('مرجع الاسترداد لدى Thawani')->required()->maxLength(255),
                        FileUpload::make('evidence_path')->label('إثبات حالة الاسترداد من Thawani')->disk('local')->directory('store-refund-evidence')->visibility('private')->required()->maxSize(5120),
                    ])
                    ->requiresConfirmation()
                    ->action(function (OrderRefundRequest $record, array $data, ManageOrderRefund $refunds): void {
                        abort_unless(static::canManage(), 403);
                        $refunds->confirmManualThawani($record, (int) Filament::auth()->id(), (string) $data['provider_refund_reference'], (string) $data['evidence_path']);
                        Notification::make()->title('تم إثبات الاسترداد والقيد المالي')->success()->send();
                    }),
                Action::make('retry_failed_refund')->label('إعادة محاولة الاسترداد الفاشل')->visible(fn (OrderRefundRequest $record): bool => static::canManage() && $record->status === 'failed' && $record->order->payment_method === 'thawani')
                    ->requiresConfirmation()
                    ->action(function (OrderRefundRequest $record, ManageOrderRefund $refunds): void {
                        abort_unless(static::canManage(), 403);
                        $refunds->retryFailedThawani($record, (int) Filament::auth()->id());
                    }),
                Action::make('credit_note')->label('إشعار دائن')->visible(fn (OrderRefundRequest $record): bool => $record->status === 'paid')
                    ->url(fn (OrderRefundRequest $record): string => route('staff.store.orders.refunds.credit-note', [$record->order_id, $record->id]))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListOrderRefunds::route('/')];
    }
}
