<?php

namespace Podio\Client\Tests\Support;

use Podio\Client\Http\TimeoutClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class TimeoutRecordingHttpClient implements TimeoutClient
{
    /**
     * @var array<int, int>
     */
    public array $timeouts = [];

    public function __construct(private FakeHttpClient $http) {}

    public function withTimeout(int $seconds): self
    {
        $this->timeouts[] = $seconds;

        return $this;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->http->sendRequest($request);
    }
}
