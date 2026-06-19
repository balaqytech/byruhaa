<?php

namespace App\Services\Thawani;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ThawaniClient
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createSession(array $payload): array
    {
        $response = Http::withHeaders([
            'thawani-api-key' => $this->secretKey(),
        ])
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->retry([100, 200], throw: false)
            ->post($this->apiUrl('/checkout/session'), $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to create Thawani checkout session.');
        }

        $body = $response->json();

        if (! is_array($body) || ! data_get($body, 'success') || ! data_get($body, 'data.session_id')) {
            throw new RuntimeException('Thawani checkout session response is invalid.');
        }

        return $body;
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveSession(string $sessionId): array
    {
        $response = Http::withHeaders([
            'thawani-api-key' => $this->secretKey(),
        ])
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->retry([100, 200], throw: false)
            ->get($this->apiUrl('/checkout/session/'.$sessionId));

        if (! $response->successful()) {
            throw new RuntimeException('Unable to retrieve Thawani checkout session.');
        }

        $body = $response->json();

        if (! is_array($body) || ! data_get($body, 'success') || ! is_array(data_get($body, 'data'))) {
            throw new RuntimeException('Thawani checkout session status response is invalid.');
        }

        return $body;
    }

    public function checkoutUrl(string $sessionId): string
    {
        $publishableKey = config('services.thawani.publishable_key');

        if (! is_string($publishableKey) || $publishableKey === '') {
            throw new RuntimeException('Thawani publishable key is not configured.');
        }

        return rtrim($this->checkoutBaseUrl(), '/').'/pay/'.$sessionId.'?key='.$publishableKey;
    }

    /**
     * @return array<string, mixed>
     */
    public function listPaymentsByInvoice(string $invoice): array
    {
        $response = Http::withHeaders([
            'thawani-api-key' => $this->secretKey(),
        ])
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->retry([100, 200], throw: false)
            ->get($this->apiUrl('/payments'), [
                'checkout_invoice' => $invoice,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to retrieve Thawani payments.');
        }

        $body = $response->json();

        if (! is_array($body) || ! data_get($body, 'success') || ! is_array(data_get($body, 'data'))) {
            throw new RuntimeException('Thawani payments response is invalid.');
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createRefund(array $payload): array
    {
        $response = Http::withHeaders([
            'thawani-api-key' => $this->secretKey(),
        ])
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->retry([100, 200], throw: false)
            ->post($this->apiUrl('/refunds'), $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to create Thawani refund.');
        }

        $body = $response->json();

        if (! is_array($body) || ! data_get($body, 'success') || ! data_get($body, 'data.refund_id')) {
            throw new RuntimeException('Thawani refund response is invalid.');
        }

        return $body;
    }

    private function secretKey(): string
    {
        $secretKey = config('services.thawani.secret_key');

        if (! is_string($secretKey) || $secretKey === '') {
            throw new RuntimeException('Thawani secret key is not configured.');
        }

        return $secretKey;
    }

    private function apiUrl(string $path): string
    {
        return rtrim($this->apiBaseUrl(), '/').'/'.ltrim($path, '/');
    }

    private function apiBaseUrl(): string
    {
        $apiBaseUrl = config('services.thawani.api_base_url');

        if (! is_string($apiBaseUrl) || $apiBaseUrl === '') {
            throw new RuntimeException('Thawani API base URL is not configured.');
        }

        return $apiBaseUrl;
    }

    private function checkoutBaseUrl(): string
    {
        $checkoutBaseUrl = config('services.thawani.checkout_base_url');

        if (! is_string($checkoutBaseUrl) || $checkoutBaseUrl === '') {
            throw new RuntimeException('Thawani checkout base URL is not configured.');
        }

        return $checkoutBaseUrl;
    }
}
