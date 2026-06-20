<?php

namespace App\Contracts\Payments;

interface PaymentGateway
{
    public function name(): string;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createSession(array $payload): array;

    /**
     * @return array<string, mixed>
     */
    public function retrieveSession(string $sessionId): array;

    public function checkoutUrl(string $sessionId): string;

    /**
     * @return array<string, mixed>
     */
    public function listPaymentsByInvoice(string $invoice): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createRefund(array $payload): array;
}
