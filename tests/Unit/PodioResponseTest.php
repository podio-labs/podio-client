<?php

use Podio\Client\PodioResponse;
use Podio\Client\RateLimitSnapshot;

test('it exposes the body, status code and headers', function () {
    $response = new PodioResponse(
        body: (object) ['ok' => true],
        statusCode: 202,
        rateLimit: new RateLimitSnapshot(limit: 100, remaining: 99),
        headers: ['X-Test' => ['value']],
    );

    expect($response->body())->toEqual((object) ['ok' => true])
        ->and($response->statusCode())->toBe(202)
        ->and($response->headers())->toBe(['X-Test' => ['value']])
        ->and($response->rateLimit()->limit())->toBe(100)
        ->and($response->rateLimit()->remaining())->toBe(99);
});

test('rateLimit falls back to an empty snapshot when none is provided', function () {
    $response = new PodioResponse(body: null);

    expect($response->rateLimit())->toBeInstanceOf(RateLimitSnapshot::class)
        ->and($response->rateLimit()->limit())->toBeNull()
        ->and($response->statusCode())->toBeNull()
        ->and($response->headers())->toBe([]);
});
