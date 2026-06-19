<?php

use Podio\Client\RetryPolicy;

test('run returns immediately when the callback succeeds', function () {
    $attempts = 0;

    $result = (new RetryPolicy)->run(function () use (&$attempts) {
        $attempts++;

        return 'ok';
    });

    expect($result)->toBe('ok')
        ->and($attempts)->toBe(1);
});

test('run retries until the callback succeeds', function () {
    $attempts = 0;

    $result = RetryPolicy::make([0, 0])->run(function () use (&$attempts) {
        $attempts++;

        if ($attempts < 3) {
            throw new RuntimeException('flaky');
        }

        return 'recovered';
    });

    expect($result)->toBe('recovered')
        ->and($attempts)->toBe(3);
});

test('run rethrows after exhausting the attempts', function () {
    $attempts = 0;
    $caught = null;

    try {
        RetryPolicy::make([0])->run(function () use (&$attempts) {
            $attempts++;

            throw new RuntimeException('always fails');
        });
    } catch (RuntimeException $exception) {
        $caught = $exception;
    }

    expect($caught->getMessage())->toBe('always fails')
        ->and($attempts)->toBe(2);
});

test('run does not retry when the when filter rejects the exception', function () {
    $attempts = 0;
    $caught = null;

    try {
        RetryPolicy::make([0, 0], when: fn (Throwable $e): bool => false)->run(function () use (&$attempts) {
            $attempts++;

            throw new RuntimeException('not retryable');
        });
    } catch (RuntimeException $exception) {
        $caught = $exception;
    }

    expect($caught->getMessage())->toBe('not retryable')
        ->and($attempts)->toBe(1);
});

test('run honours an integer attempt count and a closure backoff', function () {
    $attempts = 0;

    $result = (new RetryPolicy(2, fn (int $attempt, Throwable $e): int => 0))->run(function () use (&$attempts) {
        $attempts++;

        if ($attempts < 2) {
            throw new RuntimeException('once');
        }

        return 'done';
    });

    expect($result)->toBe('done')
        ->and($attempts)->toBe(2);
});

test('run sleeps between attempts when a positive backoff is given', function () {
    $attempts = 0;

    $result = RetryPolicy::make([1])->run(function () use (&$attempts) {
        $attempts++;

        if ($attempts < 2) {
            throw new RuntimeException('wait');
        }

        return 'slept';
    });

    expect($result)->toBe('slept')
        ->and($attempts)->toBe(2);
});
