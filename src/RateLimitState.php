<?php

namespace Podio\Client;

use Psr\Http\Message\ResponseInterface;

final class RateLimitState
{
    private ?int $limit = null;
    private ?int $remaining = null;

    public function updateFromResponse(ResponseInterface $response): void
    {
        $limit = $this->integerHeader($response, 'X-Rate-Limit-Limit');

        if ($limit !== null) {
            $this->limit = $limit;
        }

        $remaining = $this->integerHeader($response, 'X-Rate-Limit-Remaining');

        if ($remaining !== null) {
            $this->remaining = $remaining;
        }
    }

    public function snapshot(): RateLimitSnapshot
    {
        return new RateLimitSnapshot(
            limit: $this->limit,
            remaining: $this->remaining,
        );
    }

    private function integerHeader(ResponseInterface $response, string $name): ?int
    {
        $value = trim($response->getHeaderLine($name));

        return ctype_digit($value) ? (int) $value : null;
    }
}
