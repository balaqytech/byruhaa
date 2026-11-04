<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\Customer;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Illuminate\Support\Facades\DB;

class CreateCustomerAccount
{
    public function __construct(private ByruhaaWebhookSender $sender) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data): Customer
    {
        return DB::transaction(function () use ($data): Customer {
            $customer = Customer::create(collect($data)->except('password_confirmation')->all());
            $this->sender->sendCustomerRegistered($customer);

            return $customer;
        });
    }
}
