<?php

namespace App\States\Contract;

class Voided extends ContractState
{
    public static string $name = 'voided';

    public function getLabel(): string
    {
        return __('admin.statuses.voided');
    }

    public function getColor(): string
    {
        return 'gray';
    }
}
