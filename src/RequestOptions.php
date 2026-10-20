<?php

namespace Podio\Client;

final readonly class RequestOptions
{
    public function __construct(
        public ?string $fields = null,
        public ?RetryPolicy $retry = null,
        public ?int $timeout = null,
    ) {}

    public function withFields(?string $fields): self
    {
        return new self(
            fields: $fields,
            retry: $this->retry,
            timeout: $this->timeout,
        );
    }

    public function withRetry(?RetryPolicy $retry): self
    {
        return new self(
            fields: $this->fields,
            retry: $retry,
            timeout: $this->timeout,
        );
    }

    public function withTimeout(?int $timeout): self
    {
        return new self(
            fields: $this->fields,
            retry: $this->retry,
            timeout: $timeout,
        );
    }
}
