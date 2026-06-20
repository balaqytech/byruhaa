<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGateway;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class PaymentGatewayManager extends Manager
{
    public function createThawaniDriver(): PaymentGateway
    {
        return $this->container->make(ThawaniPaymentGateway::class);
    }

    public function getDefaultDriver(): string
    {
        $driver = config('payments.default', 'thawani');

        if (! is_string($driver) || $driver === '') {
            throw new InvalidArgumentException('The default payment gateway is not configured.');
        }

        return $driver;
    }
}
