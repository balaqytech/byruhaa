<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Modules\Identity\Services\PhoneNumberNormalizer;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['phone_number'] = app(PhoneNumberNormalizer::class)->normalize($data['phone_number'] ?? null);

        if (blank($data['email'] ?? null)) {
            $data['email'] = null;
        }

        return $data;
    }
}
