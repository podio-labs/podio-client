<?php

use Podio\Client\Exceptions\MissingResourceFieldException;
use Podio\Client\Exceptions\PodioAuthenticationException;
use Podio\Client\Exceptions\PodioException;
use Podio\Client\Exceptions\PodioRequestException;
use Podio\Client\RateLimitSnapshot;

test('PodioRequestException exposes the status, body and rate limit', function () {
    $snapshot = new RateLimitSnapshot(limit: 100, remaining: 0);
    $exception = new PodioRequestException(429, (object) ['error' => 'rate_limit'], 'Too many requests', $snapshot);

    expect($exception->statusCode())->toBe(429)
        ->and($exception->getMessage())->toBe('Too many requests')
        ->and($exception->getCode())->toBe(429)
        ->and($exception->responseBody())->toEqual((object) ['error' => 'rate_limit'])
        ->and($exception->rateLimit())->toBe($snapshot);
});

test('PodioRequestException allows a null rate limit', function () {
    $exception = new PodioRequestException(500, null);

    expect($exception->rateLimit())->toBeNull()
        ->and($exception->getMessage())->toBe('Podio request failed.');
});

test('MissingResourceFieldException builds a helpful message', function () {
    $exception = new MissingResourceFieldException('item', 'app');

    expect($exception->getMessage())->toContain('app')
        ->and($exception->getMessage())->toContain('item')
        ->and($exception->getMessage())->toContain('Request it explicitly');
});

test('every package exception implements the PodioException marker', function () {
    expect(new PodioRequestException(500, null))->toBeInstanceOf(PodioException::class)
        ->and(new MissingResourceFieldException('item', 'app'))->toBeInstanceOf(PodioException::class)
        ->and(new PodioAuthenticationException('nope'))->toBeInstanceOf(PodioException::class);
});
