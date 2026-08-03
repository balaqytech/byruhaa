<?php

namespace App\Modules\Events\States\Contract;

class AwaitingSignature extends ContractState
{
    public static string $name = 'awaiting_signature';

    public function getLabel(): string
    {
        return __('admin.statuses.awaiting_signature');
    }

    public function getColor(): string
    {
        return 'warning';
    }
}
