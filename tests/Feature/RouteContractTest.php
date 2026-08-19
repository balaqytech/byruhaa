<?php

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

test('the existing API route contract remains stable', function (): void {
    /** @var array<string, array{methods: list<string>, uri: string}> $contract */
    $contract = [
        'api.v1.customers.index' => ['methods' => ['GET', 'HEAD'], 'uri' => 'api/v1/customers'],
        'api.v1.customers.store' => ['methods' => ['POST'], 'uri' => 'api/v1/customers'],
        'api.v1.customers.show' => ['methods' => ['GET', 'HEAD'], 'uri' => 'api/v1/customers/{customer}'],
        'api.v1.customers.update' => ['methods' => ['PUT', 'PATCH'], 'uri' => 'api/v1/customers/{customer}'],
        'api.v1.customers.destroy' => ['methods' => ['DELETE'], 'uri' => 'api/v1/customers/{customer}'],
        'api.v1.customers.bookings.index' => ['methods' => ['GET', 'HEAD'], 'uri' => 'api/v1/customers/{customer}/bookings'],
        'api.v1.customers.bookings.store' => ['methods' => ['POST'], 'uri' => 'api/v1/customers/{customer}/bookings'],
        'api.v1.customers.bookings.show' => ['methods' => ['GET', 'HEAD'], 'uri' => 'api/v1/customers/{customer}/bookings/{booking}'],
        'api.v1.customers.bookings.payments.store' => ['methods' => ['POST'], 'uri' => 'api/v1/customers/{customer}/bookings/{booking}/payments'],
        'api.v1.customers.family-members.index' => ['methods' => ['GET', 'HEAD'], 'uri' => 'api/v1/customers/{customer}/family-members'],
        'api.v1.customers.family-members.store' => ['methods' => ['POST'], 'uri' => 'api/v1/customers/{customer}/family-members'],
        'api.v1.customers.family-members.show' => ['methods' => ['GET', 'HEAD'], 'uri' => 'api/v1/customers/{customer}/family-members/{family_member}'],
        'api.v1.customers.family-members.update' => ['methods' => ['PUT', 'PATCH'], 'uri' => 'api/v1/customers/{customer}/family-members/{family_member}'],
        'api.v1.customers.family-members.destroy' => ['methods' => ['DELETE'], 'uri' => 'api/v1/customers/{customer}/family-members/{family_member}'],
        'api.v1.customers.payments.index' => ['methods' => ['GET', 'HEAD'], 'uri' => 'api/v1/customers/{customer}/payments'],
        'api.v1.customers.payments.store' => ['methods' => ['POST'], 'uri' => 'api/v1/customers/{customer}/payments'],
        'api.v1.customers.payments.show' => ['methods' => ['GET', 'HEAD'], 'uri' => 'api/v1/customers/{customer}/payments/{payment}'],
        'api.v1.customers.payments.update' => ['methods' => ['PUT', 'PATCH'], 'uri' => 'api/v1/customers/{customer}/payments/{payment}'],
        'api.v1.customers.payments.destroy' => ['methods' => ['DELETE'], 'uri' => 'api/v1/customers/{customer}/payments/{payment}'],
        'api.v1.customers.profile.update' => ['methods' => ['PATCH'], 'uri' => 'api/v1/customers/{customer}/profile'],
        'api.v1.events.index' => ['methods' => ['GET', 'HEAD'], 'uri' => 'api/v1/events'],
        'api.v1.events.show' => ['methods' => ['GET', 'HEAD'], 'uri' => 'api/v1/events/{event}'],
        'api.v1.integrations.assistant.event-interests.show' => ['methods' => ['GET', 'HEAD'], 'uri' => 'api/v1/integrations/assistant/event-interests'],
        'api.v1.integrations.assistant.event-interests.update' => ['methods' => ['PUT'], 'uri' => 'api/v1/integrations/assistant/event-interests'],
        'api.v1.integrations.assistant.event-interests.destroy' => ['methods' => ['DELETE'], 'uri' => 'api/v1/integrations/assistant/event-interests'],
        'api.webhooks.thawani' => ['methods' => ['POST'], 'uri' => 'api/webhooks/thawani'],
    ];

    $apiRoutes = collect(RouteFacade::getRoutes()->getRoutes())
        ->filter(fn (Route $route): bool => str_starts_with($route->uri(), 'api/'))
        ->keyBy(fn (Route $route): ?string => $route->getName());

    $legacyApiRoutes = $apiRoutes->reject(fn (Route $route): bool => str_contains($route->uri(), 'integrations/uchat/store'));

    expect($legacyApiRoutes)->toHaveCount(count($contract));

    foreach ($contract as $name => $expected) {
        $route = $legacyApiRoutes->get($name);

        expect($route)->toBeInstanceOf(Route::class)
            ->and($route->uri())->toBe($expected['uri'])
            ->and($route->methods())->toBe($expected['methods'])
            ->and($route->gatherMiddleware())->toContain('api');
    }

    expect($legacyApiRoutes->filter(fn (Route $route): bool => str_contains($route->uri(), 'store'))->all())
        ->toBeEmpty();
});
