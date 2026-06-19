<?php

namespace Podio\Client;

final readonly class PodioResponse
{
    /**
     * @param  array<string, array<int, string>>  $headers
     */
    public function __construct(
        private mixed $body,
        private ?int $statusCode = null,
        private ?RateLimitSnapshot $rateLimit = null,
        private array $headers = [],
    ) {}

    public function body(): mixed
    {
        return $this->body;
    }

    public function rateLimit(): RateLimitSnapshot
    {
        return $this->rateLimit ?? new RateLimitSnapshot;
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function headers(): array
    {
        return $this->headers;
    }
}
