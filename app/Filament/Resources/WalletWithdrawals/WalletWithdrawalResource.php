<?php

namespace App\Filament\Resources\WalletWithdrawals;

use App\Filament\Resources\ShieldResource;
use App\Filament\Resources\WalletWithdrawals\Pages\ListWalletWithdrawals;
use App\Filament\Resources\WalletWithdrawals\Pages\ViewWalletWithdrawal;
use App\Filament\Resources\WalletWithdrawals\Schemas\WalletWithdrawalInfolist;
use App\Modules\Finance\Actions\ManageWalletWithdrawal;
use App\Modules\Finance\Models\WalletWithdrawalRequest;
use App\Modules\Identity\Models\User;
use App\Support\MoneyFormatter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class WalletWithdrawalResource extends ShieldResource
{
    protected static ?string $model = WalletWithdrawalRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function getModelLabel(): string
    {
        return 'طلب سحب محفظة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'طلبات سحب المحافظ';
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

        return $user instanceof User && ($user->isPanelAdministrator() || $user->can('Update:WalletWithdrawalRequest'));
    }

    public static function infolist(Schema $schema): Schema
    {
        return WalletWithdrawalInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->with(['wallet.minorProfile.familyMember', 'guardian'])->latest('id'))
            ->columns([
                TextColumn::make('reference')->label('المرجع')->searchable()->copyable(),
                TextColumn::make('guardian.name')->label('ولي الأمر')->searchable(),
                TextColumn::make('wallet.minorProfile.familyMember.name')->label('القائد'),
                TextColumn::make('amount_baisa')->label('المبلغ')->formatStateUsing(fn (int $state, WalletWithdrawalRequest $record): string => MoneyFormatter::baisa($state, $record->currency)),
                TextColumn::make('method')->label('الوسيلة')->formatStateUsing(fn (?string $state): string => $state === 'bank' ? 'Bank' : 'Cash'),
                TextColumn::make('bank_name')->label('البنك')->placeholder('-'),
                TextColumn::make('bank_beneficiary_name')->label('اسم المستفيد المسجل')->placeholder('-'),
                TextColumn::make('bank_account')->label('حساب ولي الأمر')->placeholder('-')->visible(fn (): bool => static::canManage())->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('payout_reference')->label('مرجع الصرف')->placeholder('-'),
                TextColumn::make('created_at')->label('تاريخ الطلب')->dateTime()->sortable(),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض التفاصيل والتغييرات'),
                Action::make('approve')->label('موافقة')->visible(fn (WalletWithdrawalRequest $record): bool => static::canManage() && $record->status === 'pending')
                    ->schema([Textarea::make('note')->label('ملاحظة المراجعة')])
                    ->action(function (WalletWithdrawalRequest $record, array $data, ManageWalletWithdrawal $withdrawals): void {
                        abort_unless(static::canManage(), 403);
                        $withdrawals->approve($record, (int) Filament::auth()->id(), $data['note'] ?? null);
                        Notification::make()->title('تمت الموافقة على طلب السحب')->success()->send();
                    }),
                Action::make('reject')->label('رفض')->color('danger')->visible(fn (WalletWithdrawalRequest $record): bool => static::canManage() && $record->status === 'pending')
                    ->schema([Textarea::make('reason')->label('سبب الرفض')->required()])
                    ->action(function (WalletWithdrawalRequest $record, array $data, ManageWalletWithdrawal $withdrawals): void {
                        abort_unless(static::canManage(), 403);
                        $withdrawals->reject($record, (int) Filament::auth()->id(), (string) $data['reason']);
                    }),
                Action::make('start_payout')->label('بدء الصرف')->visible(fn (WalletWithdrawalRequest $record): bool => static::canManage() && $record->status === 'approved')
                    ->requiresConfirmation()
                    ->action(function (WalletWithdrawalRequest $record, ManageWalletWithdrawal $withdrawals): void {
                        abort_unless(static::canManage(), 403);
                        $withdrawals->startPayout($record, (int) Filament::auth()->id());
                    }),
                Action::make('manual_review')->label('مراجعة نتيجة التحويل')->visible(fn (WalletWithdrawalRequest $record): bool => static::canManage() && $record->status === 'paying' && $record->method === 'bank')
                    ->schema([Textarea::make('reason')->label('سبب عدم وضوح نتيجة التحويل')->required()])
                    ->action(function (WalletWithdrawalRequest $record, array $data, ManageWalletWithdrawal $withdrawals): void {
                        abort_unless(static::canManage(), 403);
                        $withdrawals->markManualReview($record, (int) Filament::auth()->id(), (string) $data['reason']);
                    }),
                Action::make('confirm_payout')->label('تأكيد الصرف')->color('success')->visible(fn (WalletWithdrawalRequest $record): bool => static::canManage() && in_array($record->status, ['paying', 'manual_review'], true))
                    ->schema([
                        TextInput::make('payout_reference')->label('مرجع الإيصال أو التحويل')->required()->maxLength(255),
                        FileUpload::make('evidence_path')->label('إثبات التحويل البنكي')->disk('local')->directory('withdrawal-evidence')->visibility('private')->required(fn (WalletWithdrawalRequest $record): bool => $record->method === 'bank')->maxSize(5120),
                        Checkbox::make('beneficiary_verified')->label('تحققت من أن حساب Bank transfer باسم وليّ الأمر المسجل')->required(fn (WalletWithdrawalRequest $record): bool => $record->method === 'bank'),
                    ])
                    ->requiresConfirmation()
                    ->action(function (WalletWithdrawalRequest $record, array $data, ManageWalletWithdrawal $withdrawals): void {
                        abort_unless(static::canManage(), 403);
                        $withdrawals->completePayout($record, (int) Filament::auth()->id(), (string) $data['payout_reference'], $data['evidence_path'] ?? null, (bool) ($data['beneficiary_verified'] ?? false));
                        Notification::make()->title('تم توثيق الصرف والقيد المالي')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWalletWithdrawals::route('/'),
            'view' => ViewWalletWithdrawal::route('/{record}'),
        ];
    }
}
