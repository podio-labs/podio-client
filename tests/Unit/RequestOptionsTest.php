<?php

use Podio\Client\RequestOptions;
use Podio\Client\RetryPolicy;

test('it defaults to no fields and no retry policy', function () {
    $options = new RequestOptions;

    expect($options->fields)->toBeNull()
        ->and($options->retry)->toBeNull();
});

test('withFields returns a new instance preserving the retry policy', function () {
    $retry = new RetryPolicy;
    $options = (new RequestOptions(retry: $retry))->withFields('items.fields(files)');

    expect($options->fields)->toBe('items.fields(files)')
        ->and($options->retry)->toBe($retry);
});

test('withRetry returns a new instance preserving the fields', function () {
    $retry = new RetryPolicy;
    $options = (new RequestOptions(fields: 'a'))->withRetry($retry);

    expect($options->retry)->toBe($retry)
        ->and($options->fields)->toBe('a');
});

test('the withers do not mutate the original options', function () {
    $original = new RequestOptions;

    $original->withFields('x');
    $original->withRetry(new RetryPolicy);

    expect($original->fields)->toBeNull()
        ->and($original->retry)->toBeNull();
});
