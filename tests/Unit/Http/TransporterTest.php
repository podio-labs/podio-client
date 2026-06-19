<?php

use Podio\Client\Exceptions\PodioRequestException;
use Podio\Client\PodioResponse;
use Podio\Client\Tests\Support\FakeHttpClient;

test('it builds a json request with the bearer header', function () {
    $http = new FakeHttpClient([httpResponse(200, [], '{}')]);

    transporter($http)->send('POST', '/item/1', ['json' => ['a' => 1]], 'BEARER');

    $request = $http->requests[0];

    expect((string) $request->getUri())->toBe('https://api.podio.com/item/1')
        ->and($request->getHeaderLine('Accept'))->toBe('application/json')
        ->and($request->getHeaderLine('Authorization'))->toBe('OAuth2 BEARER')
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and((string) $request->getBody())->toBe('{"a":1}');
});

test('it omits the authorization header when no bearer is given', function () {
    $http = new FakeHttpClient([httpResponse(200, [], '{}')]);

    transporter($http)->send('GET', '/item/1', [], null);

    expect($http->requests[0]->hasHeader('Authorization'))->toBeFalse();
});

test('it builds a form-encoded request', function () {
    $http = new FakeHttpClient([httpResponse(200, [], '{}')]);

    transporter($http)->send('POST', '/x', ['form_params' => ['a' => '1', 'b' => '2']], null);

    expect($http->requests[0]->getHeaderLine('Content-Type'))->toBe('application/x-www-form-urlencoded')
        ->and((string) $http->requests[0]->getBody())->toBe('a=1&b=2');
});

test('it sends a raw body verbatim', function () {
    $http = new FakeHttpClient([httpResponse(200, [], '{}')]);

    transporter($http)->send('POST', '/x', ['body' => 'raw-payload'], null);

    expect((string) $http->requests[0]->getBody())->toBe('raw-payload');
});

test('it appends query parameters to the uri', function () {
    $http = new FakeHttpClient([httpResponse(200, [], '{}')]);

    transporter($http)->send('GET', '/x', ['query' => ['fields' => 'a,b']], null);

    expect((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/x?fields=a%2Cb');
});

test('it forwards custom headers and skips empty ones', function () {
    $http = new FakeHttpClient([httpResponse(200, [], '{}')]);

    transporter($http)->send('GET', '/x', ['headers' => [
        'Accept' => 'application/json',
        'X-Empty' => '',
        'X-Null' => null,
        'X-List' => ['one', 'two'],
    ]], null);

    $request = $http->requests[0];

    expect($request->hasHeader('X-Empty'))->toBeFalse()
        ->and($request->hasHeader('X-Null'))->toBeFalse()
        ->and($request->getHeaderLine('X-List'))->toBe('one, two');
});

test('it normalizes the base url and path slashes', function () {
    $http = new FakeHttpClient([httpResponse(200, [], '{}')]);

    transporter($http, 'https://api.podio.com/')->send('GET', 'item/1', [], null);

    expect((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/item/1');
});

test('it builds a multipart body and escapes header values', function () {
    $http = new FakeHttpClient([httpResponse(200, [], '{}')]);

    transporter($http)->send('POST', '/file/', ['multipart' => [
        ['name' => 'source', 'contents' => 'bytes', 'filename' => 'a"b.jpg', 'content_type' => 'image/jpeg'],
        ['name' => 'filename', 'contents' => 'a"b.jpg'],
    ]], null);

    $request = $http->requests[0];
    $body = (string) $request->getBody();

    expect($request->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data; boundary=')
        ->and($body)->toContain('Content-Disposition: form-data; name="source"; filename="a\"b.jpg"')
        ->and($body)->toContain('Content-Type: image/jpeg')
        ->and($body)->toContain('Content-Disposition: form-data; name="filename"');
});

test('it escapes quotes and backslashes and strips CR/LF from header values', function () {
    $http = new FakeHttpClient([httpResponse(200, [], '{}')]);

    transporter($http)->send('POST', '/file/', ['multipart' => [
        ['name' => 'source', 'contents' => 'bytes', 'filename' => "evil\".\\path\r\n.jpg"],
    ]], null);

    $body = (string) $http->requests[0]->getBody();

    expect($body)->toContain('filename="evil\\".\\\\path.jpg"')
        ->and($body)->not->toContain("\r\n.jpg")
        ->and(substr_count($body, '\\\\'))->toBe(1);
});

test('response decodes json, updates the rate limit and returns a PodioResponse', function () {
    $response = transporter(new FakeHttpClient([]))->response(httpResponse(200, [
        'X-Rate-Limit-Limit' => '100',
        'X-Rate-Limit-Remaining' => '99',
    ], '{"ok":true}'));

    expect($response)->toBeInstanceOf(PodioResponse::class)
        ->and($response->body()->ok)->toBeTrue()
        ->and($response->statusCode())->toBe(200)
        ->and($response->rateLimit()->limit())->toBe(100)
        ->and($response->rateLimit()->remaining())->toBe(99);
});

test('response returns null body for an empty payload', function () {
    expect(transporter(new FakeHttpClient([]))->response(httpResponse(200, [], ''))->body())->toBeNull();
});

test('response returns the raw string body when raw is expected', function () {
    expect(transporter(new FakeHttpClient([]))->response(httpResponse(200, [], 'jpeg-bytes'), expectRaw: true)->body())
        ->toBe('jpeg-bytes');
});

test('response throws on invalid json for a successful response', function () {
    expect(fn () => transporter(new FakeHttpClient([]))->response(httpResponse(200, [], 'not-json')))
        ->toThrow(RuntimeException::class, 'Podio response body is not valid JSON');
});

test('response throws a PodioRequestException for failures', function () {
    $caught = null;

    try {
        transporter(new FakeHttpClient([]))->response(httpResponse(429, [
            'X-Rate-Limit-Remaining' => '0',
        ], '{"error_description":"rate_limit"}'));
    } catch (PodioRequestException $exception) {
        $caught = $exception;
    }

    expect($caught?->statusCode())->toBe(429)
        ->and($caught?->getMessage())->toBe('Podio request failed with status 429: rate_limit')
        ->and($caught?->responseBody()->error_description)->toBe('rate_limit')
        ->and($caught?->rateLimit()?->remaining())->toBe(0);
});

test('failure messages fall back to the error field then a generic message', function () {
    $errorOnly = fn () => transporter(new FakeHttpClient([]))->response(httpResponse(400, [], '{"error":"bad_request"}'));
    expect($errorOnly)->toThrow(PodioRequestException::class, 'bad_request');

    $generic = fn () => transporter(new FakeHttpClient([]))->response(httpResponse(400, [], '{}'));
    expect($generic)->toThrow(PodioRequestException::class, 'Podio request failed with status 400.');
});

test('a failed response with a non-json body keeps the raw string at the 400 boundary', function () {
    $caught = null;

    try {
        transporter(new FakeHttpClient([]))->response(httpResponse(400, [], '<html>boom</html>'));
    } catch (PodioRequestException $exception) {
        $caught = $exception;
    }

    expect($caught?->statusCode())->toBe(400)
        ->and($caught?->responseBody())->toBe('<html>boom</html>');
});

test('isExpiredTokenResponse detects only expired token 401 responses', function () {
    $transporter = transporter(new FakeHttpClient([]));

    expect($transporter->isExpiredTokenResponse(httpResponse(401, [], '{"error_description":"expired_token"}')))->toBeTrue()
        ->and($transporter->isExpiredTokenResponse(httpResponse(401, [], '{"error":"unauthorized"}')))->toBeFalse()
        ->and($transporter->isExpiredTokenResponse(httpResponse(200, [], '{}')))->toBeFalse()
        ->and($transporter->isExpiredTokenResponse(httpResponse(401, [], 'not-json')))->toBeFalse();
});

test('rateLimit exposes the live state', function () {
    $transporter = transporter(new FakeHttpClient([]));

    $transporter->response(httpResponse(200, ['X-Rate-Limit-Remaining' => '42'], '{}'));

    expect($transporter->rateLimit()->snapshot()->remaining())->toBe(42);
});
