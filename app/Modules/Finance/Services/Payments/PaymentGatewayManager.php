<?php

namespace App\Modules\Finance\Services\Payments;

use App\Modules\Finance\Contracts\PaymentGateway;
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
