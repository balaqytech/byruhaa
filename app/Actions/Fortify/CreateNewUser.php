<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\Customer;
use App\Services\PhoneNumberNormalizer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(private PhoneNumberNormalizer $phoneNumberNormalizer) {}

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
            'email' => ['nullable', 'required_without:phone_number', 'string', 'email', 'max:255', Rule::unique(Customer::class)],
            'phone_number' => ['nullable', 'required_without:email', 'string', 'phone:OM', Rule::unique(Customer::class)],
            'password' => $this->passwordRules(),
        ])->validate();

        return Customer::create([
            'name' => $input['name'],
            'email' => $input['email'] ?: null,
            'phone_number' => $input['phone_number'] ?: null,
            'password' => $input['password'],
        ]);
    }
}
