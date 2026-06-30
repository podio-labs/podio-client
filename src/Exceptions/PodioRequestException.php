<?php

namespace Podio\Client\Exceptions;

use Podio\Client\RateLimitSnapshot;
use RuntimeException;

final class PodioRequestException extends RuntimeException implements PodioException
{
    public function __construct(
        private readonly int $statusCode,
        private readonly mixed $responseBody,
        string $message = 'Podio request failed.',
        private readonly ?RateLimitSnapshot $rateLimit = null,
    ) {
        parent::__construct($message, $statusCode);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function responseBody(): mixed
    {
        return $this->responseBody;
    }

    public function rateLimit(): ?RateLimitSnapshot
    {
        return $this->rateLimit;
    }
}
