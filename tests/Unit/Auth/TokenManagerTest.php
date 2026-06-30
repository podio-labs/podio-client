<?php

use Podio\Client\Auth\AccessToken;
use Podio\Client\Auth\Grants\AppGrant;
use Podio\Client\Auth\Grants\AuthorizationCodeGrant;
use Podio\Client\Auth\Grants\Grant;
use Podio\Client\Auth\Grants\PasswordGrant;
use Podio\Client\Auth\PodioCredentials;
use Podio\Client\Auth\TokenManager;
use Podio\Client\Exceptions\PodioAuthenticationException;
use Podio\Client\Tests\Support\ArrayCache;
use Podio\Client\Tests\Support\FakeHttpClient;
use Psr\SimpleCache\CacheInterface;

function tokenManager(
    FakeHttpClient $http,
    ?CacheInterface $cache = null,
    ?Grant $grant = new PasswordGrant('user', 'pass'),
    ?AccessToken $accessToken = null,
): TokenManager {
    return new TokenManager(
        new PodioCredentials('client-id', 'client-secret'),
        transporter($http),
        $cache,
        'podio.access_token',
        $grant,
        $accessToken,
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

test('ensure posts the authorization-code grant to the token endpoint', function () {
    $http = new FakeHttpClient([oauthTokenResponse()]);

    tokenManager($http, grant: new AuthorizationCodeGrant('auth-code', 'https://app.test/callback'))->ensure();

    $body = (string) $http->allRequests[0]->getBody();

    expect($body)->toContain('grant_type=authorization_code')
        ->and($body)->toContain('code=auth-code')
        ->and($body)->toContain('redirect_uri=https%3A%2F%2Fapp.test%2Fcallback');
});

test('ensure posts the app grant to the token endpoint', function () {
    $http = new FakeHttpClient([oauthTokenResponse()]);

    tokenManager($http, grant: new AppGrant(424242, 'app-token'))->ensure();

    $body = (string) $http->allRequests[0]->getBody();

    expect($body)->toContain('grant_type=app')
        ->and($body)->toContain('app_id=424242')
        ->and($body)->toContain('app_token=app-token');
});

test('ensure reuses the in-memory token without touching the transport again', function () {
    $manager = tokenManager(new FakeHttpClient([oauthTokenResponse()]));

    expect($manager->ensure())->toBe('access-token')
        ->and($manager->ensure())->toBe('access-token');
});

test('ensure returns a seeded, still-valid access token without authenticating', function () {
    $manager = tokenManager(
        new FakeHttpClient([]),
        accessToken: AccessToken::fromValues('seeded-token', time() + 3600),
    );

    expect($manager->ensure())->toBe('seeded-token')
        ->and($manager->current()?->value())->toBe('seeded-token');
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

test('ensure refreshes an expired seeded token with its refresh token', function () {
    $http = new FakeHttpClient([oauthTokenResponse()]);

    $manager = tokenManager(
        $http,
        grant: null,
        accessToken: AccessToken::fromValues('stale-token', time() - 10, 'the-refresh-token'),
    );

    expect($manager->ensure())->toBe('access-token');

    $body = (string) $http->allRequests[0]->getBody();

    expect($body)->toContain('grant_type=refresh_token')
        ->and($body)->toContain('refresh_token=the-refresh-token');
});

test('refresh forgets the token and authenticates again with the password grant', function () {
    $cache = new ArrayCache;
    $cache->set('podio.access_token', ['access_token' => 'stale', 'expires_at' => 9999999999]);

    $manager = tokenManager(new FakeHttpClient([oauthTokenResponse()]), $cache);

    expect($manager->refresh())->toBe('access-token')
        ->and(AccessToken::fromCacheValue($cache->get('podio.access_token'))?->value())->toBe('access-token');
});

test('current returns the resolved access token', function () {
    $manager = tokenManager(new FakeHttpClient([oauthTokenResponse()]));

    expect($manager->current())->toBeNull();

    $manager->ensure();

    expect($manager->current())->toBeInstanceOf(AccessToken::class)
        ->and($manager->current()?->value())->toBe('access-token');
});

test('authenticate throws when no grant is configured and no refreshable token exists', function () {
    $manager = tokenManager(new FakeHttpClient([]), grant: null);

    expect(fn () => $manager->ensure())
        ->toThrow(PodioAuthenticationException::class, 'no grant configured');
});
