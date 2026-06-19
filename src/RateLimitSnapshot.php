<?php

namespace Podio\Client;

final readonly class RateLimitSnapshot
{
    public function __construct(
        private ?int $limit = null,
        private ?int $remaining = null,
    ) {}

    public function limit(): ?int
    {
        return $this->limit;
    }

    public function remaining(): ?int
    {
        return $this->remaining;
    }
}
