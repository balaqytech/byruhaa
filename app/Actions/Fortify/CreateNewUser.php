<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\Customer;
use App\Services\PhoneNumberNormalizer;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        private PhoneNumberNormalizer $phoneNumberNormalizer,
        private ByruhaaWebhookSender $webhookSender,
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string|null>  $input
     */
    public function create(array $input): Customer
    {
        $input['phone_number'] = $this->phoneNumberNormalizer->normalize($input['phone_number'] ?? null);

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique(Customer::class)],
            'phone_number' => ['required', 'string', 'phone:INTERNATIONAL,OM', Rule::unique(Customer::class)],
            'password' => $this->passwordRules(),
        ])->validate();

        $customer = Customer::create([
            'name' => $input['name'],
            'email' => $input['email'] ?: null,
            'phone_number' => $input['phone_number'],
            'password' => $input['password'],
        ]);

        $this->webhookSender->sendCustomerRegistered($customer);

        return $customer;
    }
}
