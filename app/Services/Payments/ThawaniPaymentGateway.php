<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Exceptions\PaymentGatewayException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Jkbroot\Thawani\Helpers\ValidationHelper;
use RuntimeException;

class ThawaniPaymentGateway implements PaymentGateway
{
    private const REQUIRED_SESSION_FIELDS = ['client_reference_id', 'mode', 'products', 'success_url', 'cancel_url', 'metadata'];

    private const PRODUCT_FIELDS = ['name', 'quantity', 'unit_amount'];

    public function name(): string
    {
        return 'thawani';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createSession(array $payload): array
    {
        ValidationHelper::validateSessionData($payload, self::REQUIRED_SESSION_FIELDS, self::PRODUCT_FIELDS);

        $body = $this->request('post', '/checkout/session', $payload, 'Unable to create Thawani checkout session.');
        $sessionId = data_get($body, 'data.session_id');

        if (! data_get($body, 'success') || ! is_scalar($sessionId) || (string) $sessionId === '') {
            throw $this->gatewayResponseException($body, 'Thawani checkout session response is invalid.');
        }

        data_set($body, 'data.redirect_url', $this->checkoutUrl((string) $sessionId));

        return $body;
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveSession(string $sessionId): array
    {
        $body = $this->request('get', "/checkout/session/{$sessionId}", failureMessage: 'Unable to retrieve Thawani checkout session.');

        if (! data_get($body, 'success') || ! is_array(data_get($body, 'data'))) {
            throw $this->gatewayResponseException($body, 'Thawani checkout session status response is invalid.');
        }

        return $body;
    }

    /**
     * @return array<string, mixed>
     */
    public function cancelSession(string $sessionId): array
    {
        $body = $this->request('post', "/checkout/{$sessionId}/cancel", failureMessage: 'Unable to cancel Thawani checkout session.');

        if (! data_get($body, 'success')) {
            throw $this->gatewayResponseException($body, 'Thawani checkout session cancellation response is invalid.');
        }

        return $body;
    }

    public function checkoutUrl(string $sessionId): string
    {
        return rtrim($this->checkoutBaseUrl(), '/').'/'.$sessionId.'?key='.$this->publishableKey();
    }

    /**
     * @return array<string, mixed>
     */
    public function listPaymentsByInvoice(string $invoice): array
    {
        $body = $this->request('get', '/payments', [
            'checkout_invoice' => $invoice,
        ], 'Unable to retrieve Thawani payments.');

        if (! data_get($body, 'success') || ! is_array(data_get($body, 'data'))) {
            throw $this->gatewayResponseException($body, 'Thawani payments response is invalid.');
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createRefund(array $payload): array
    {
        $body = $this->request('post', '/refunds', $payload, 'Unable to create Thawani refund.');

        if (! data_get($body, 'success') || ! data_get($body, 'data.refund_id')) {
            throw $this->gatewayResponseException($body, 'Thawani refund response is invalid.');
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function request(string $method, string $uri, array $data = [], string $failureMessage = 'Thawani API request failed.'): array
    {
        $response = Http::baseUrl($this->apiBaseUrl())
            ->withHeaders(['thawani-api-key' => $this->secretKey()])
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->retry([100, 200], throw: false)
            ->{$method}($uri, $data);

        $body = $this->responseBody($response);

        if ($response->successful()) {
            return $body;
        }

        throw new PaymentGatewayException(
            $this->failureMessage($body, $failureMessage),
            [
                'status' => $response->status(),
                'response' => $body,
            ],
            (int) data_get($body, 'code', $response->status()),
        );
    }

    private function apiBaseUrl(): string
    {
        return $this->configuredString('base_url', 'Thawani API base URL is not configured.');
    }

    private function checkoutBaseUrl(): string
    {
        return $this->configuredString('checkout_base_url', 'Thawani checkout base URL is not configured.');
    }

    private function publishableKey(): string
    {
        return $this->configuredString('publishable_key', 'Thawani publishable key is not configured.');
    }

    private function secretKey(): string
    {
        return $this->configuredString('secret_key', 'Thawani secret key is not configured.');
    }

    private function configuredString(string $key, string $message): string
    {
        $mode = config('thawani.mode', 'test');

        if (! is_string($mode) || $mode === '') {
            throw new RuntimeException('Thawani mode is not configured.');
        }

        $value = config("thawani.{$mode}.{$key}");

        if (! is_string($value) || $value === '') {
            throw new RuntimeException($message);
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function responseBody(Response $response): array
    {
        $body = $response->json();

        if (is_array($body)) {
            return $body;
        }

        return [
            'body' => $response->body(),
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function gatewayResponseException(array $body, string $fallback): PaymentGatewayException
    {
        return new PaymentGatewayException(
            $this->failureMessage($body, $fallback),
            ['response' => $body],
            (int) data_get($body, 'code', 0),
        );
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function failureMessage(array $body, string $fallback): string
    {
        $description = data_get($body, 'description')
            ?? data_get($body, 'message')
            ?? data_get($body, 'error');

        return is_scalar($description) && (string) $description !== ''
            ? (string) $description
            : $fallback;
    }
}
