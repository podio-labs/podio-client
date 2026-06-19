<?php

namespace Podio\Client\Tests\Support;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class FakeHttpClient implements ClientInterface
{
    /**
     * Requests excluding the OAuth token exchange (the common assertion target).
     *
     * @var array<int, RequestInterface>
     */
    public array $requests = [];

    /**
     * Every request, including the OAuth token exchange.
     *
     * @var array<int, RequestInterface>
     */
    public array $allRequests = [];

    /**
     * @param  array<int, ResponseInterface>  $responses
     */
    public function __construct(private array $responses) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->allRequests[] = $request;

        if (! str_ends_with((string) $request->getUri(), '/oauth/token')) {
            $this->requests[] = $request;
        }

        return array_shift($this->responses)
            ?? throw new RuntimeException('No queued response available.');
    }
}
