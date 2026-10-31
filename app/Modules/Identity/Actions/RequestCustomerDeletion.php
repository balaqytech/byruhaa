<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\CustomerDeletionRequest;
use Illuminate\Support\Facades\DB;

class RequestCustomerDeletion
{
    public function execute(Customer $customer, ?string $reason): CustomerDeletionRequest
    {
        return DB::transaction(function () use ($customer, $reason): CustomerDeletionRequest {
            $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();

            return $customer->deletionRequests()->where('status', 'pending')->first()
                ?? $customer->deletionRequests()->create(['status' => 'pending', 'reason' => $reason, 'requested_at' => now()]);
        });
    }
}
