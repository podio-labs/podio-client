<?php

use Podio\Client\Auth\AccessToken;
use Podio\Client\Endpoints\AppsEndpoint;
use Podio\Client\Endpoints\CommentsEndpoint;
use Podio\Client\Endpoints\EmbedEndpoint;
use Podio\Client\Endpoints\FilesEndpoint;
use Podio\Client\Endpoints\HooksEndpoint;
use Podio\Client\Endpoints\ItemsEndpoint;
use Podio\Client\Endpoints\SearchEndpoint;
use Podio\Client\Exceptions\PodioRequestException;
use Podio\Client\Factory;
use Podio\Client\PodioClient;
use Podio\Client\PodioResponse;
use Podio\Client\RateLimitSnapshot;
use Podio\Client\Tests\Support\FakeHttpClient;

test('factory returns a configurable factory', function () {
    expect(PodioClient::factory())->toBeInstanceOf(Factory::class);
});

test('it exposes every endpoint accessor', function () {
    [$client] = podioClientWith([]);

    expect($client->apps())->toBeInstanceOf(AppsEndpoint::class)
        ->and($client->items())->toBeInstanceOf(ItemsEndpoint::class)
        ->and($client->hooks())->toBeInstanceOf(HooksEndpoint::class)
        ->and($client->files())->toBeInstanceOf(FilesEndpoint::class)
        ->and($client->comments())->toBeInstanceOf(CommentsEndpoint::class)
        ->and($client->search())->toBeInstanceOf(SearchEndpoint::class)
        ->and($client->embed())->toBeInstanceOf(EmbedEndpoint::class);
});

test('rateLimit returns an immutable snapshot of the current state', function () {
    [$client] = podioClientWith([
        httpResponse(200, ['X-Rate-Limit-Limit' => '15000', 'X-Rate-Limit-Remaining' => '14999'], '{"app_id":1}'),
    ]);

    $client->apps()->get(1);

    expect($client->rateLimit())->toBeInstanceOf(RateLimitSnapshot::class)
        ->and($client->rateLimit()->limit())->toBe(15000)
        ->and($client->rateLimit()->remaining())->toBe(14999);
});

test('send returns the full response and forwards raw bodies and headers', function () {
    $http = fakeHttp([httpResponse(202, ['X-Podio-Test' => 'ok'], 'accepted')]);
    $client = podioClient($http);

    $response = $client->send('POST', '/gateway/test', [
        'headers' => ['Content-Type' => 'text/plain'],
        'body' => 'payload',
        'raw' => true,
    ]);

    expect($response)->toBeInstanceOf(PodioResponse::class)
        ->and($response->body())->toBe('accepted')
        ->and($response->statusCode())->toBe(202)
        ->and($response->headers()['X-Podio-Test'] ?? [])->toBe(['ok'])
        ->and((string) $http->requests[0]->getBody())->toBe('payload');
});

test('send retries once after an expired token response', function () {
    $http = fakeHttp([
        httpResponse(401, [], '{"error_description":"expired_token"}'),
        oauthTokenResponse(),
        httpResponse(200, [], '{"item_id":5}'),
    ]);
    $client = podioClient($http);

    $item = $client->items()->get(5);

    expect($item->item_id)->toBe(5)
        ->and($http->requests)->toHaveCount(2);
});

test('a non-expired 401 throws without refreshing or retrying', function () {
    $http = fakeHttp([httpResponse(401, [], '{"error":"unauthorized","error_description":"no access"}')]);
    $client = podioClient($http);

    $caught = null;

    try {
        $client->items()->get(5);
    } catch (PodioRequestException $exception) {
        $caught = $exception;
    }

    expect($caught?->statusCode())->toBe(401)
        ->and($http->requests)->toHaveCount(1);
});

test('a failed authentication surfaces as a PodioRequestException', function () {
    $http = new FakeHttpClient([httpResponse(401, [], '{"error":"invalid_grant","error_description":"invalid credentials"}')]);
    $client = podioClient($http);

    expect(fn () => $client->items()->get(5))
        ->toThrow(PodioRequestException::class, 'invalid credentials');
});

test('authenticate resolves and returns the access token', function () {
    [$client] = podioClientWith([]);

    expect($client->token())->toBeNull();

    $token = $client->authenticate();

    expect($token)->toBeInstanceOf(AccessToken::class)
        ->and($token->value())->toBe('access-token')
        ->and($client->token())->toBe($token);
});
