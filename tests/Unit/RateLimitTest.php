<?php

use Podio\Client\RateLimitSnapshot;
use Podio\Client\RateLimitState;

test('updateFromResponse reads the rate limit headers', function () {
    $state = new RateLimitState;

    $state->updateFromResponse(httpResponse(200, [
        'X-Rate-Limit-Limit' => '15000',
        'X-Rate-Limit-Remaining' => '14999',
    ], ''));

    expect($state->snapshot()->limit())->toBe(15000)
        ->and($state->snapshot()->remaining())->toBe(14999);
});

test('updateFromResponse keeps previous values when headers are absent', function () {
    $state = new RateLimitState;

    $state->updateFromResponse(httpResponse(200, [
        'X-Rate-Limit-Limit' => '15000',
        'X-Rate-Limit-Remaining' => '14999',
    ], ''));

    $state->updateFromResponse(httpResponse(200, [], ''));

    expect($state->snapshot()->limit())->toBe(15000)
        ->and($state->snapshot()->remaining())->toBe(14999);
});

test('updateFromResponse ignores non-numeric header values', function () {
    $state = new RateLimitState;

    $state->updateFromResponse(httpResponse(200, [
        'X-Rate-Limit-Limit' => 'lots',
        'X-Rate-Limit-Remaining' => 'few',
    ], ''));

    expect($state->snapshot()->limit())->toBeNull()
        ->and($state->snapshot()->remaining())->toBeNull();
});

test('a fresh snapshot exposes null values', function () {
    $snapshot = new RateLimitSnapshot;

    expect($snapshot->limit())->toBeNull()
        ->and($snapshot->remaining())->toBeNull();
});
