<?php

namespace App\States\Contract;

class AwaitingSignature extends ContractState
{
    public static string $name = 'awaiting_signature';

    public function label(): string
    {
        return 'Awaiting signature';
    }
}
