<?php

namespace App\Enums;

enum EventInterestSource: string
{
    case Website = 'website';
    case Assistant = 'assistant';
    case Admin = 'admin';
}
