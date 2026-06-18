<?php

namespace App\States\Contract;

class Voided extends ContractState
{
    public static string $name = 'voided';

    public function label(): string
    {
        return __('admin.statuses.voided');
    }
}
