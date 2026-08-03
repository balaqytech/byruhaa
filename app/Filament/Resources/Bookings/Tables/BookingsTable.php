<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Modules\Events\Models\Booking;
use App\Modules\Events\Services\BookingApprovalService;
use App\Modules\Events\States\Booking\Cancelled;
use App\Modules\Events\States\Booking\PendingReview;
use App\Modules\Events\States\Booking\Rejected;
use App\Modules\Identity\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label(__('admin.fields.reference'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label(__('admin.fields.customer'))
                    ->searchable(),
                TextColumn::make('event.name')
                    ->label(__('admin.fields.event'))
                    ->searchable(),
                TextColumn::make('family_members_count')
                    ->label(__('admin.fields.family_members'))
                    ->counts('familyMembers'),
                TextColumn::make('state')
                    ->label(__('admin.fields.state'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->label(__('admin.fields.state'))
                    ->options([
                        'pending_review' => __('admin.statuses.pending_review'),
                        'approved' => __('admin.statuses.approved'),
                        'rejected' => __('admin.statuses.rejected'),
                        'cancelled' => __('admin.statuses.cancelled'),
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('admin.actions.approve'))
                    ->visible(fn (Booking $record): bool => $record->state instanceof PendingReview)
                    ->requiresConfirmation()
                    ->action(function (Booking $record, BookingApprovalService $approvalService): void {
                        $reviewer = auth('web')->user();

                        if (! $reviewer instanceof User) {
                            abort(403);
                        }

                        $approvalService->approve($record, $reviewer);
                    }),
                Action::make('reject')
                    ->label(__('admin.actions.reject'))
                    ->visible(fn (Booking $record): bool => $record->state instanceof PendingReview)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Booking $record): void {
                        $record->forceFill([
                            'reviewed_by_user_id' => auth()->id(),
                            'reviewed_at' => now(),
                        ])->save();

                        $record->state->transitionTo(Rejected::class);
                    }),
                Action::make('cancel')
                    ->label(__('admin.actions.cancel'))
                    ->visible(fn (Booking $record): bool => ! $record->state instanceof Cancelled)
                    ->color('warning')
                    ->schema([
                        Textarea::make('reason')
                            ->label('سبب الإلغاء')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->requiresConfirmation()
                    ->action(function (Booking $record, array $data): void {
                        $record->forceFill([
                            'cancellation_reason' => trim((string) $data['reason']),
                        ])->save();
                        $record->state->transitionTo(Cancelled::class);
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
