<?php

namespace App\Data;

class UchatIdentity
{
    public function __construct(public readonly string $phone, public readonly ?int $customerId) {}
}
