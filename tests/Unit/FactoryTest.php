<?php

use Podio\Client\PodioClient;
use Podio\Client\Tests\Support\ArrayCache;

test('make requires client credentials', function () {
    expect(fn () => PodioClient::factory()->make())
        ->toThrow(InvalidArgumentException::class, 'Podio client credentials are required.');
});

test('make requires password authentication', function () {
    expect(fn () => PodioClient::factory()->withClientCredentials('id', 'secret')->make())
        ->toThrow(InvalidArgumentException::class, 'Podio password authentication is required.');
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
