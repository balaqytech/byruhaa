<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Modules\Identity\Actions\ReviewCustomerDeletion;
use App\Modules\Identity\Models\CustomerDeletionRequest;
use App\Modules\Identity\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeletionRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'deletionRequests';

    public function table(Table $table): Table
    {
        return $table->heading(__('api_uchat.deletion_requests'))->columns([
            TextColumn::make('status')->label(__('api_uchat.status'))->badge(),
            TextColumn::make('reason')->label(__('api_uchat.reason'))->wrap(),
            TextColumn::make('requested_at')->label(__('api_uchat.requested_at'))->dateTime(),
            TextColumn::make('reviewed_at')->label(__('api_uchat.reviewed_at'))->dateTime(),
            TextColumn::make('review_notes')->label(__('api_uchat.review_notes'))->wrap(),
        ])->recordActions([
            Action::make('review')->label(__('api_uchat.review'))->visible(function (CustomerDeletionRequest $record): bool {
                $reviewer = auth('web')->user();

                return $record->status === 'pending' && $reviewer instanceof User && ($reviewer->isPanelAdministrator() || $reviewer->can('Delete:Customer'));
            })
                ->schema([
                    Select::make('status')->options(['rejected' => __('api_uchat.rejected'), 'closed' => __('api_uchat.closed')])->required(),
                    Textarea::make('notes')->label(__('api_uchat.review_notes'))->required()->maxLength(5000),
                ])->action(function (CustomerDeletionRequest $record, array $data, ReviewCustomerDeletion $review): void {
                    $reviewer = auth('web')->user();
                    abort_unless($reviewer instanceof User, 403);
                    $review->execute($record, $reviewer, $data['status'], $data['notes']);
                }),
        ]);
    }
}
