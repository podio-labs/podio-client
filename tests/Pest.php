<?php

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Podio\Client\Http\Transporter;
use Podio\Client\PodioClient;
use Podio\Client\RateLimitState;
use Podio\Client\Tests\Support\FakeHttpClient;

pest()->extend(TestCase::class)->in('Unit');

function psr17(): Psr17Factory
{
    return new Psr17Factory;
}

/**
 * @param  array<string, string|array<int, string>>  $headers
 */
function httpResponse(int $status, array $headers, string $body): Response
{
    return new Response($status, $headers, $body);
}

function oauthTokenResponse(): Response
{
    return new Response(200, [], (string) json_encode(['access_token' => 'access-token', 'expires_in' => 3600]));
}

/**
 * @param  array<int, Response>  $responses
 */
function fakeHttp(array $responses, bool $withOAuth = true): FakeHttpClient
{
    if ($withOAuth) {
        array_unshift($responses, oauthTokenResponse());
    }

    return new FakeHttpClient($responses);
}

function podioClient(FakeHttpClient $http): PodioClient
{
    $factory = psr17();

    return PodioClient::factory()
        ->withClientCredentials('client-id', 'client-secret')
        ->withPasswordAuth('username', 'password')
        ->withHttpClient($http)
        ->withHttpFactories($factory, $factory, $factory)
        ->make();
}

/**
 * @param  array<int, Response>  $responses
 * @return array{0: PodioClient, 1: FakeHttpClient}
 */
function podioClientWith(array $responses): array
{
    $http = fakeHttp($responses);

    return [podioClient($http), $http];
}

function transporter(FakeHttpClient $http, string $baseUrl = 'https://api.podio.com'): Transporter
{
    $factory = psr17();

    return new Transporter($http, $factory, $factory, $factory, $baseUrl, new RateLimitState);
}
