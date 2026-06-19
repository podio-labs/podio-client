<?php

namespace Podio\Client\Http;

use Podio\Client\Exceptions\PodioRequestException;
use Podio\Client\PodioResponse;
use Podio\Client\RateLimitState;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

final readonly class Transporter
{
    public function __construct(
        private ClientInterface $http,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private UriFactoryInterface $uriFactory,
        private string $baseUrl,
        private RateLimitState $rateLimit,
    ) {}

    public function rateLimit(): RateLimitState
    {
        return $this->rateLimit;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function send(string $method, string $uri, array $options = [], ?string $bearer = null): ResponseInterface
    {
        return $this->http->sendRequest(
            $this->buildRequest($method, $uri, $options, $bearer)
        );
    }

    public function response(ResponseInterface $response, bool $expectRaw = false): PodioResponse
    {
        $this->rateLimit->updateFromResponse($response);

        $body = $this->decode($response, $expectRaw);

        return new PodioResponse(
            body: $this->throwIfFailed($response, $body),
            statusCode: $response->getStatusCode(),
            rateLimit: $this->rateLimit->snapshot(),
            headers: $response->getHeaders(),
        );
    }

    public function isExpiredTokenResponse(ResponseInterface $response): bool
    {
        if ($response->getStatusCode() !== 401) {
            return false;
        }

        $body = $this->decode($response);

        return is_object($body) && ($body->error_description ?? null) === 'expired_token';
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function buildRequest(string $method, string $uri, array $options, ?string $bearer): RequestInterface
    {
        $uri = $this->resolveUri($uri);

        if (isset($options['query']) && is_array($options['query']) && $options['query'] !== []) {
            $uri = $uri->withQuery(http_build_query($options['query']));
        }

        $request = $this->requestFactory
            ->createRequest($method, $uri)
            ->withHeader('Accept', 'application/json');

        if ($bearer !== null) {
            $request = $request->withHeader('Authorization', 'OAuth2 ' . $bearer);
        }

        if (isset($options['headers']) && is_array($options['headers'])) {
            foreach ($options['headers'] as $name => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $request = $request->withHeader((string) $name, is_array($value) ? implode(', ', $value) : (string) $value);
            }
        }

        if (isset($options['json'])) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream((string) json_encode($options['json'])));
        }

        if (isset($options['form_params']) && is_array($options['form_params'])) {
            $request = $request
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($this->streamFactory->createStream(http_build_query($options['form_params'])));
        }

        if (isset($options['multipart']) && is_array($options['multipart'])) {
            $multipart = $this->buildMultipart($options['multipart']);
            $request = $request
                ->withHeader('Content-Type', $multipart['contentType'])
                ->withBody($this->streamFactory->createStream($multipart['body']));
        }

        if (isset($options['body'])) {
            $request = $request->withBody($this->streamFactory->createStream((string) $options['body']));
        }

        return $request;
    }

    /**
     * @param  array<int, array{name: string, contents: string, filename?: string, content_type?: string}>  $parts
     * @return array{body: string, contentType: string}
     */
    private function buildMultipart(array $parts): array
    {
        $boundary = '----PodioBoundary' . bin2hex(random_bytes(8));
        $eol = "\r\n";
        $body = '';

        foreach ($parts as $part) {
            $body .= '--' . $boundary . $eol;
            $body .= 'Content-Disposition: form-data; name="' . $this->headerValue($part['name']) . '"';

            if (isset($part['filename'])) {
                $body .= '; filename="' . $this->headerValue($part['filename']) . '"';
            }

            $body .= $eol;

            if (isset($part['content_type'])) {
                $body .= 'Content-Type: ' . $part['content_type'] . $eol;
            }

            $body .= $eol . $part['contents'] . $eol;
        }

        $body .= '--' . $boundary . '--' . $eol;

        return [
            'body' => $body,
            'contentType' => 'multipart/form-data; boundary=' . $boundary,
        ];
    }

    private function headerValue(string $value): string
    {
        return str_replace(['\\', '"', "\r", "\n"], ['\\\\', '\\"', '', ''], $value);
    }

    private function resolveUri(string $uri): UriInterface
    {
        return $this->uriFactory->createUri(
            rtrim($this->baseUrl, '/') . '/' . ltrim($uri, '/')
        );
    }

    private function throwIfFailed(ResponseInterface $response, mixed $body): mixed
    {
        if ($response->getStatusCode() < 400) {
            return $body;
        }

        throw new PodioRequestException(
            statusCode: $response->getStatusCode(),
            responseBody: $body,
            message: $this->exceptionMessage($response, $body),
            rateLimit: $this->rateLimit->snapshot(),
        );
    }

    private function exceptionMessage(ResponseInterface $response, mixed $body): string
    {
        $message = is_object($body) ? ($body->error_description ?? $body->error ?? null) : null;

        if (is_string($message) && $message !== '') {
            return 'Podio request failed with status ' . $response->getStatusCode() . ': ' . $message;
        }

        return 'Podio request failed with status ' . $response->getStatusCode() . '.';
    }

    private function decode(ResponseInterface $response, bool $expectRaw = false): mixed
    {
        $body = (string) $response->getBody();

        if ($body === '') {
            return null;
        }

        if ($expectRaw) {
            return $body;
        }

        $decoded = json_decode($body);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        if ($response->getStatusCode() >= 400) {
            return $body;
        }

        throw new RuntimeException('Podio response body is not valid JSON. Use the raw request option for raw responses.');
    }
}
