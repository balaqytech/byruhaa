<?php

use App\Http\Middleware\AuthenticateUchat;
use App\Http\Middleware\ResolveUchatIdentity;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

test('the API route contract separates public endpoints from the unified UChat boundary', function (): void {
    /** @var array<string, array{methods: list<string>, uri: string}> $contract */
    $contract = [
        'api.v1.events.index' => ['methods' => ['GET', 'HEAD'], 'uri' => 'api/v1/events'],
        'api.v1.events.show' => ['methods' => ['GET', 'HEAD'], 'uri' => 'api/v1/events/{event}'],
        'api.webhooks.thawani' => ['methods' => ['POST'], 'uri' => 'api/webhooks/thawani'],
    ];

    $apiRoutes = collect(RouteFacade::getRoutes()->getRoutes())
        ->filter(fn (Route $route): bool => str_starts_with($route->uri(), 'api/'))
        ->keyBy(fn (Route $route): ?string => $route->getName());

    $legacyApiRoutes = $apiRoutes->reject(fn (Route $route): bool => str_contains($route->uri(), 'integrations/uchat/'));

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
    $uchatRoutes = $apiRoutes->diffKeys($legacyApiRoutes);
    expect($uchatRoutes)->not->toBeEmpty();
    foreach ($uchatRoutes as $route) {
        expect($route->gatherMiddleware())->toContain(AuthenticateUchat::class, ResolveUchatIdentity::class);
    }
    expect(RouteFacade::has('api.v1.customers.index'))->toBeFalse();
    expect(RouteFacade::has('api.v1.integrations.uchat.bookings.store'))->toBeTrue();

});

test('every Postman request targets an existing unified UChat route with the phone header', function (): void {
    $collection = json_decode(file_get_contents(base_path('byruha_api_v1.postman_collection.json')), true, flags: JSON_THROW_ON_ERROR);
    expect($collection['auth']['type'])->toBe('bearer');
    foreach ($collection['item'] as $group) {
        foreach ($group['item'] as $item) {
            $request = $item['request'];
            $url = str_replace('{{base_url}}', 'http://localhost', $request['url']);
            $url = preg_replace('/\{\{[^}]+\}\}/', '1', $url);
            $route = RouteFacade::getRoutes()->match(Request::create($url, $request['method']));
            expect($route->uri())->toStartWith('api/v1/integrations/uchat/');
            expect(collect($request['header'])->pluck('key')->all())->toContain('X-WhatsApp-Phone');
        }
    }
});
