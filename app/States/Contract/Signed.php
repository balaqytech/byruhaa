<?php

namespace App\States\Contract;

class Signed extends ContractState
{
    public static string $name = 'signed';

    public function label(): string
    {
        return 'Signed';
    }
}
