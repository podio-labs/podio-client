<?php

namespace Podio\Client;

final readonly class RequestOptions
{
    public function __construct(
        public ?string $fields = null,
        public ?RetryPolicy $retry = null,
    ) {}

    public function withFields(?string $fields): self
    {
        return new self(
            fields: $fields,
            retry: $this->retry,
        );
    }

    public function withRetry(?RetryPolicy $retry): self
    {
        return new self(
            fields: $this->fields,
            retry: $retry,
        );
    }
}
