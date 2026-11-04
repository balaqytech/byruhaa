<?php

namespace App\Filament\Resources\WalletWithdrawals\Schemas;

use App\Modules\Finance\Models\WalletWithdrawalRequest;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Support\MoneyFormatter;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use OwenIt\Auditing\Models\Audit;

class WalletWithdrawalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('تفاصيل طلب السحب')
                ->columns(3)
                ->schema([
                    TextEntry::make('reference')->label('المرجع')->copyable(),
                    TextEntry::make('status')->label('الحالة')->badge()->formatStateUsing(fn (string $state): string => self::statusLabel($state)),
                    TextEntry::make('amount_baisa')->label('المبلغ')->state(fn (WalletWithdrawalRequest $record): string => MoneyFormatter::baisa($record->amount_baisa, $record->currency)),
                    TextEntry::make('guardian.name')->label('ولي الأمر'),
                    TextEntry::make('wallet.minorProfile.familyMember.name')->label('القائد'),
                    TextEntry::make('method')->label('وسيلة الصرف')->formatStateUsing(fn (string $state): string => $state === 'bank' ? 'Bank transfer' : 'Cash'),
                    TextEntry::make('bank_name')->label('البنك')->placeholder('—'),
                    TextEntry::make('bank_beneficiary_name')->label('اسم المستفيد')->placeholder('—'),
                    TextEntry::make('payout_reference')->label('مرجع الصرف')->copyable()->placeholder('—'),
                    TextEntry::make('review_note')->label('ملاحظة المراجعة')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('created_at')->label('تاريخ الطلب')->dateTime(),
                    TextEntry::make('approved_at')->label('تاريخ الموافقة')->dateTime()->placeholder('—'),
                    TextEntry::make('paid_at')->label('تاريخ الصرف')->dateTime()->placeholder('—'),
                ]),
            Section::make('سجل التغييرات')
                ->description('التغييرات المسجلة على الطلب، من الأحدث إلى الأقدم.')
                ->schema([
                    RepeatableEntry::make('audit_history')
                        ->hiddenLabel()
                        ->state(fn (WalletWithdrawalRequest $record): array => self::history($record))
                        ->schema([
                            TextEntry::make('event')->label('الحدث'),
                            TextEntry::make('actor')->label('المنفّذ'),
                            TextEntry::make('occurred_at')->label('الوقت')->dateTime(),
                            TextEntry::make('changes')->label('ما تغيّر')->bulleted()->listWithLineBreaks()->columnSpanFull(),
                        ])
                        ->columns(3),
                ]),
        ]);
    }

    /** @return array<int, array{event: string, actor: string, occurred_at: mixed, changes: array<int, string>}> */
    private static function history(WalletWithdrawalRequest $withdrawal): array
    {
        return $withdrawal->audits()->with('user')->latest('id')->get()
            ->map(function (Audit $audit) use ($withdrawal): array {
                $newValues = $audit->new_values ?? [];
                $oldValues = $audit->old_values ?? [];
                $actor = match (true) {
                    $audit->user instanceof Customer => 'ولي الأمر: '.$audit->user->name,
                    $audit->user instanceof User => 'الموظف: '.$audit->user->name,
                    default => 'النظام',
                };

                return [
                    'event' => match ($audit->event) {
                        'created' => 'إنشاء الطلب',
                        'updated' => isset($newValues['status']) ? 'تغيير الحالة إلى '.self::statusLabel((string) $newValues['status']) : 'تحديث الطلب',
                        default => $audit->event,
                    },
                    'actor' => $actor,
                    'occurred_at' => $audit->created_at,
                    'changes' => self::changes($oldValues, $newValues, $withdrawal->currency),
                ];
            })->all();
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @return array<int, string>
     */
    private static function changes(array $oldValues, array $newValues, string $currency): array
    {
        $labels = [
            'wallet_id' => 'المحفظة',
            'guardian_customer_id' => 'ولي الأمر',
            'reference' => 'المرجع',
            'amount_baisa' => 'المبلغ',
            'currency' => 'العملة',
            'status' => 'الحالة',
            'method' => 'وسيلة الصرف',
            'bank_name' => 'البنك',
            'bank_beneficiary_name' => 'المستفيد',
            'payout_reference' => 'مرجع الصرف',
            'evidence_path' => 'إثبات الصرف',
            'review_note' => 'ملاحظة المراجعة',
            'approved_by_user_id' => 'معرّف الموافق',
            'paid_by_user_id' => 'معرّف منفّذ الصرف',
            'approved_at' => 'وقت الموافقة',
            'paid_at' => 'وقت الصرف',
        ];

        return collect(array_unique([...array_keys($oldValues), ...array_keys($newValues)]))
            ->map(function (string $field) use ($labels, $oldValues, $newValues, $currency): string {
                $label = $labels[$field] ?? $field;
                $new = self::value($field, $newValues[$field] ?? null, $currency);

                if (! array_key_exists($field, $oldValues) || $oldValues[$field] === null || $oldValues[$field] === '') {
                    return $label.': '.$new;
                }

                $old = self::value($field, $oldValues[$field], $currency);

                return $label.': من '.$old.' إلى '.$new;
            })->all();
    }

    private static function value(string $field, mixed $value, string $currency): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($field) {
            'status' => self::statusLabel((string) $value),
            'amount_baisa' => MoneyFormatter::baisa((int) $value, $currency),
            'method' => $value === 'bank' ? 'Bank transfer' : 'Cash',
            'evidence_path' => 'مرفق',
            default => (string) $value,
        };
    }

    private static function statusLabel(string $status): string
    {
        return [
            'pending' => 'قيد المراجعة',
            'approved' => 'موافق عليه',
            'paying' => 'قيد الصرف',
            'paid' => 'تم الصرف',
            'rejected' => 'مرفوض',
            'cancelled' => 'ملغى',
            'manual_review' => 'مراجعة يدوية',
        ][$status] ?? $status;
    }
}
