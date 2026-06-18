<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Models\Booking;
use App\Models\User;
use App\Services\BookingApprovalService;
use App\States\Booking\BookingState;
use App\States\Booking\Cancelled;
use App\States\Booking\PendingReview;
use App\States\Booking\Rejected;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->searchable(),
                TextColumn::make('event.name')
                    ->searchable(),
                TextColumn::make('family_members_count')
                    ->label('Family members')
                    ->counts('familyMembers'),
                TextColumn::make('state')
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof BookingState ? $state->label() : (string) $state)
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->options([
                        'pending_review' => 'Pending review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
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
                    ->visible(fn (Booking $record): bool => ! $record->state instanceof Cancelled)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Booking $record) => $record->state->transitionTo(Cancelled::class)),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
