<?php

namespace App\Enums;

enum WebhookDeliveryStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case FinalFailed = 'final_failed';
}
