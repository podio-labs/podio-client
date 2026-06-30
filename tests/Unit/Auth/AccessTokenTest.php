<?php

use Podio\Client\Auth\AccessToken;
use Podio\Client\Exceptions\PodioAuthenticationException;

test('fromOAuthResponse builds a token from a valid response', function () {
    $token = AccessToken::fromOAuthResponse((object) ['access_token' => 'abc', 'expires_in' => 3600]);

    expect($token->value())->toBe('abc')
        ->and($token->refreshToken())->toBeNull()
        ->and($token->isExpired())->toBeFalse()
        ->and($token->ttl())->toBeGreaterThan(3500)
        ->and($token->ttl())->toBeLessThanOrEqual(3600);
});

test('fromOAuthResponse captures the refresh token when present', function () {
    $token = AccessToken::fromOAuthResponse(
        (object) ['access_token' => 'abc', 'expires_in' => 3600, 'refresh_token' => 'refresh-abc']
    );

    expect($token->refreshToken())->toBe('refresh-abc');
});

test('fromOAuthResponse ignores an empty refresh token', function () {
    $token = AccessToken::fromOAuthResponse(
        (object) ['access_token' => 'abc', 'expires_in' => 3600, 'refresh_token' => '']
    );

    expect($token->refreshToken())->toBeNull();
});

test('fromOAuthResponse throws when the access token is missing or empty', function (mixed $response) {
    expect(fn () => AccessToken::fromOAuthResponse($response))
        ->toThrow(PodioAuthenticationException::class, 'did not include an access token');
})->with([
    'not an object' => ['a string'],
    'missing field' => [(object) ['expires_in' => 3600]],
    'empty token' => [(object) ['access_token' => '', 'expires_in' => 3600]],
    'non-string token' => [(object) ['access_token' => 123, 'expires_in' => 3600]],
]);

test('fromOAuthResponse accepts the minimal valid expiration of one second', function () {
    $token = AccessToken::fromOAuthResponse((object) ['access_token' => 'abc', 'expires_in' => 1]);

    expect($token->value())->toBe('abc')
        ->and($token->ttl())->toBe(1);
});

test('fromOAuthResponse throws when the expiration is missing or invalid', function (mixed $response) {
    expect(fn () => AccessToken::fromOAuthResponse($response))
        ->toThrow(PodioAuthenticationException::class, 'did not include a valid expiration');
})->with([
    'missing expires_in' => [(object) ['access_token' => 'abc']],
    'non-numeric' => [(object) ['access_token' => 'abc', 'expires_in' => 'soon']],
    'below one' => [(object) ['access_token' => 'abc', 'expires_in' => 0]],
]);

test('fromValues builds a token from explicit values', function () {
    $expiresAt = time() + 1800;
    $token = AccessToken::fromValues('seeded-token', $expiresAt, 'seeded-refresh');

    expect($token->value())->toBe('seeded-token')
        ->and($token->expiresAt())->toBe($expiresAt)
        ->and($token->refreshToken())->toBe('seeded-refresh')
        ->and($token->isExpired())->toBeFalse();
});

test('fromValues defaults the refresh token to null', function () {
    $token = AccessToken::fromValues('seeded-token', time() + 1800);

    expect($token->refreshToken())->toBeNull();
});

test('toCacheValue and fromCacheValue round-trip a token', function () {
    $token = AccessToken::fromOAuthResponse((object) ['access_token' => 'abc', 'expires_in' => 3600]);

    $cached = $token->toCacheValue();

    expect($cached)->toHaveKeys(['access_token', 'expires_at'])
        ->and($cached['access_token'])->toBe('abc');

    $restored = AccessToken::fromCacheValue($cached);

    expect($restored)->toBeInstanceOf(AccessToken::class)
        ->and($restored->value())->toBe('abc');
});

test('fromCacheValue returns null for unusable cache values', function (mixed $value) {
    expect(AccessToken::fromCacheValue($value))->toBeNull();
})->with([
    'not an array' => ['a string'],
    'missing token' => [['expires_at' => 9999999999]],
    'empty token' => [['access_token' => '', 'expires_at' => 9999999999]],
    'non-string token' => [['access_token' => 123, 'expires_at' => 9999999999]],
    'non-numeric expiry' => [['access_token' => 'abc', 'expires_at' => 'later']],
    'expired' => [['access_token' => 'abc', 'expires_at' => 1]],
]);

test('the cache value never carries the refresh token', function () {
    $token = AccessToken::fromOAuthResponse(
        (object) ['access_token' => 'abc', 'expires_in' => 3600, 'refresh_token' => 'refresh-abc']
    );

    expect($token->toCacheValue())->not->toHaveKey('refresh_token')
        ->and(AccessToken::fromCacheValue($token->toCacheValue())?->refreshToken())->toBeNull();
});
