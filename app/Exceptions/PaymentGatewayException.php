<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class PaymentGatewayException extends Exception
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        string $message,
        private array $payload = [],
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'error' => $this->getMessage(),
            ...$this->payload,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->payload();
    }
}
