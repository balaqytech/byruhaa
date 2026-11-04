<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\CustomerDeletionRequest;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewCustomerDeletion
{
    public function execute(CustomerDeletionRequest $request, User $reviewer, string $status, string $notes): void
    {
        abort_unless($reviewer->isPanelAdministrator() || $reviewer->can('Delete:Customer'), 403);
        validator(['status' => $status, 'notes' => $notes], ['status' => ['required', 'in:rejected,closed'], 'notes' => ['required', 'string', 'max:5000']])->validate();
        DB::transaction(function () use ($request, $reviewer, $status, $notes): void {
            $request = CustomerDeletionRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'pending') {
                throw ValidationException::withMessages(['status' => __('api_uchat.request_already_reviewed')]);
            }
            $request->update(['status' => $status, 'review_notes' => $notes, 'reviewed_by_user_id' => $reviewer->id, 'reviewed_at' => now()]);
        });
    }
}
