<?php

namespace App\Filament\Resources\MinorProfiles\Tables;

use App\Modules\Identity\Actions\ManageMinorPosCredential;
use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Identity\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MinorProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member_code')->label(__('admin_minors.member_code'))->searchable(),
                TextColumn::make('familyMember.name')->label(__('admin_minors.name'))->searchable(),
                TextColumn::make('familyMember.customer.name')->label(__('admin_minors.guardian'))->searchable(),
                TextColumn::make('status')->label(__('admin_minors.status'))->badge()
                    ->formatStateUsing(fn (MinorProfileStatus $state): string => __('admin_minors.statuses.'.$state->value)),
                IconColumn::make('wallet_spending_enabled')->label(__('admin_minors.wallet_spending_enabled'))->boolean(),
                IconColumn::make('direct_payment_enabled')->label(__('admin_minors.direct_payment_enabled'))->boolean(),
                TextColumn::make('activated_at')->label(__('admin_minors.activated_at'))->dateTime()->placeholder('-')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('admin_minors.status'))->options(__('admin_minors.statuses')),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make(),
                Action::make('issuePosCard')
                    ->label('إصدار بطاقة QR')
                    ->visible(fn (MinorProfile $record): bool => self::canManageCards() && $record->status === MinorProfileStatus::Active)
                    ->requiresConfirmation()
                    ->modalDescription('الإصدار الجديد يبطل البطاقة السابقة فورًا. سلّم البطاقة المطبوعة للقائد فقط.')
                    ->action(function (MinorProfile $record, ManageMinorPosCredential $credentials): void {
                        abort_unless(self::canManageCards(), 403);
                        abort_unless($record->fresh()?->status === MinorProfileStatus::Active, 422);
                        $credentials->issue($record);
                        Notification::make()->title('تم إصدار بطاقة QR. استخدم زر الطباعة لتسليمها.')->success()->send();
                    }),
                Action::make('printPosCard')
                    ->label('طباعة QR')
                    ->visible(fn (MinorProfile $record): bool => self::canManageCards() && $record->posCredential?->token_ciphertext !== null && $record->posCredential->card_revoked_at === null)
                    ->url(fn (MinorProfile $record): string => route('staff.pos-cards.print', $record))
                    ->openUrlInNewTab(),
            ]);
    }

    private static function canManageCards(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && ($user->isPanelAdministrator() || $user->can('Manage:PosCards'));
    }
}
