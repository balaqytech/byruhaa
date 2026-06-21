<?php

namespace App\States\Contract;

class Signed extends ContractState
{
    public static string $name = 'signed';

    public function getLabel(): string
    {
        return __('admin.statuses.signed');
    }

    public function getColor(): string
    {
        return 'success';
    }
}
