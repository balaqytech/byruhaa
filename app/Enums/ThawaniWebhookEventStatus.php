<?php

namespace App\Enums;

enum ThawaniWebhookEventStatus: string
{
    case Received = 'received';
    case Processed = 'processed';
    case Unmatched = 'unmatched';
    case Rejected = 'rejected';
    case Failed = 'failed';
}
