<?php

use Podio\Client\Auth\AccessToken;
use Podio\Client\Auth\PodioCredentials;
use Podio\Client\Auth\TokenManager;
use Podio\Client\Tests\Support\ArrayCache;
use Podio\Client\Tests\Support\FakeHttpClient;
use Psr\SimpleCache\CacheInterface;

function tokenManager(FakeHttpClient $http, ?CacheInterface $cache = null): TokenManager
{
    return new TokenManager(
        new PodioCredentials('client-id', 'client-secret', 'user', 'pass'),
        transporter($http),
        $cache,
        'podio.access_token',
    );
}

test('ensure authenticates with the password grant and stores the token', function () {
    $cache = new ArrayCache;
    $manager = tokenManager(new FakeHttpClient([oauthTokenResponse()]), $cache);

    expect($manager->ensure())->toBe('access-token')
        ->and($cache->has('podio.access_token'))->toBeTrue()
        ->and(AccessToken::fromCacheValue($cache->get('podio.access_token'))?->value())->toBe('access-token');
});

test('ensure posts the password grant with all credentials to the token endpoint', function () {
    $http = new FakeHttpClient([oauthTokenResponse()]);

    tokenManager($http)->ensure();

    $request = $http->allRequests[0];
    $body = (string) $request->getBody();

    expect((string) $request->getUri())->toBe('https://api.podio.com/oauth/token')
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/x-www-form-urlencoded')
        ->and($body)->toContain('grant_type=password')
        ->and($body)->toContain('username=user')
        ->and($body)->toContain('password=pass')
        ->and($body)->toContain('client_id=client-id')
        ->and($body)->toContain('client_secret=client-secret');
});

test('ensure reuses the in-memory token without touching the transport again', function () {
    $manager = tokenManager(new FakeHttpClient([oauthTokenResponse()]));

    expect($manager->ensure())->toBe('access-token')
        ->and($manager->ensure())->toBe('access-token');
});

test('ensure promotes a valid cached token without authenticating', function () {
    $cache = new ArrayCache;
    $cache->set('podio.access_token', AccessToken::fromOAuthResponse(
        (object) ['access_token' => 'cached-token', 'expires_in' => 3600]
    )->toCacheValue());

    $manager = tokenManager(new FakeHttpClient([]), $cache);

    expect($manager->ensure())->toBe('cached-token');
});

test('ensure works without a cache, re-authenticating per process', function () {
    $manager = tokenManager(new FakeHttpClient([oauthTokenResponse()]), null);

    expect($manager->ensure())->toBe('access-token');
});

test('refresh forgets the token and authenticates again', function () {
    $cache = new ArrayCache;
    $cache->set('podio.access_token', ['access_token' => 'stale', 'expires_at' => 9999999999]);

    $manager = tokenManager(new FakeHttpClient([oauthTokenResponse()]), $cache);

    expect($manager->refresh())->toBe('access-token')
        ->and(AccessToken::fromCacheValue($cache->get('podio.access_token'))?->value())->toBe('access-token');
});
