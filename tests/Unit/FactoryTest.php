<?php

use Podio\Client\Events\TokenRefreshed;
use Podio\Client\PodioClient;
use Podio\Client\Tests\Support\ArrayCache;
use Podio\Client\Tests\Support\EventRecorder;

test('make requires client credentials', function () {
    expect(fn () => PodioClient::factory()->make())
        ->toThrow(InvalidArgumentException::class, 'Podio client credentials are required.');
});

test('make requires an authentication grant or seeded token', function () {
    expect(fn () => PodioClient::factory()->withClientCredentials('id', 'secret')->make())
        ->toThrow(InvalidArgumentException::class, 'Podio authentication is required.');
});

test('make builds a client using PSR discovery when no transport is injected', function () {
    $client = PodioClient::factory()
        ->withClientCredentials('id', 'secret')
        ->withPasswordAuth('user', 'pass')
        ->make();

    expect($client)->toBeInstanceOf(PodioClient::class);
});

test('make accepts an injected base url, token cache and http factories', function () {
    $factory = psr17();

    $client = PodioClient::factory()
        ->withClientCredentials('id', 'secret')
        ->withPasswordAuth('user', 'pass')
        ->withBaseUrl('https://example.test')
        ->withTokenCache(new ArrayCache, 'custom.key')
        ->withHttpClient(fakeHttp([], withOAuth: false))
        ->withHttpFactories($factory, $factory, $factory)
        ->make();

    expect($client)->toBeInstanceOf(PodioClient::class);
});

test('make builds a client with authorization-code authentication', function () {
    $client = PodioClient::factory()
        ->withClientCredentials('id', 'secret')
        ->withAuthorizationCodeAuth('auth-code', 'https://app.test/callback')
        ->make();

    expect($client)->toBeInstanceOf(PodioClient::class);
});

test('make builds a client with app authentication', function () {
    $client = PodioClient::factory()
        ->withClientCredentials('id', 'secret')
        ->withAppAuth(424242, 'app-token')
        ->make();

    expect($client)->toBeInstanceOf(PodioClient::class);
});

test('make builds a client from a seeded access token', function () {
    $client = PodioClient::factory()
        ->withClientCredentials('id', 'secret')
        ->withToken('seeded-token', time() + 3600, 'seeded-refresh')
        ->make();

    expect($client)->toBeInstanceOf(PodioClient::class)
        ->and($client->token()?->accessToken())->toBe('seeded-token');
});

test('make hands the event dispatcher to the token manager', function () {
    $factory = psr17();
    $events = new EventRecorder;

    PodioClient::factory()
        ->withClientCredentials('id', 'secret')
        ->withPasswordAuth('user', 'pass')
        ->withEventDispatcher($events)
        ->withHttpClient(fakeHttp([]))
        ->withHttpFactories($factory, $factory, $factory)
        ->make()
        ->authenticate();

    expect($events->events)->toHaveCount(1)
        ->and($events->events[0])->toBeInstanceOf(TokenRefreshed::class);
});

test('a stored token with its own base url sends the calls there', function () {
    $http = fakeHttp([httpResponse(200, [], '{"org_id":1}')], withOAuth: false);
    $factory = psr17();

    PodioClient::factory()
        ->withClientCredentials('id', 'secret')
        ->withToken('stored-token', time() + 3600, 'stored-refresh', 'https://api.podio.example')
        ->withHttpClient($http)
        ->withHttpFactories($factory, $factory, $factory)
        ->make()
        ->organizations()
        ->get(1);

    expect((string) $http->requests[0]->getUri())->toBe('https://api.podio.example/org/1');
});

test('make asks for the token at the given token url', function () {
    $http = fakeHttp([httpResponse(200, [], '{"org_id":1}')]);
    $factory = psr17();

    PodioClient::factory()
        ->withClientCredentials('id', 'secret')
        ->withPasswordAuth('user', 'pass')
        ->withTokenUrl('https://auth.podio.example/oauth/token')
        ->withHttpClient($http)
        ->withHttpFactories($factory, $factory, $factory)
        ->make()
        ->organizations()
        ->get(1);

    expect((string) $http->allRequests[0]->getUri())->toBe('https://auth.podio.example/oauth/token')
        ->and((string) $http->allRequests[1]->getUri())->toBe('https://api.podio.com/org/1');
});
