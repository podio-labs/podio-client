<?php

namespace Podio\Client\Auth;

use RuntimeException;

final readonly class AccessToken
{
    public function __construct(
        private string $value,
        private int $expiresAt,
    ) {}

    public static function fromOAuthResponse(mixed $response): self
    {
        $accessToken = is_object($response) ? ($response->access_token ?? null) : null;
        $expiresIn = is_object($response) ? ($response->expires_in ?? null) : null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Podio authentication response did not include an access token.');
        }

        if (! is_numeric($expiresIn) || (int) $expiresIn < 1) {
            throw new RuntimeException('Podio authentication response did not include a valid expiration.');
        }

        return new self($accessToken, time() + (int) $expiresIn);
    }

    public static function fromCacheValue(mixed $value): ?self
    {
        if (! is_array($value)) {
            return null;
        }

        $accessToken = $value['access_token'] ?? null;
        $expiresAt = $value['expires_at'] ?? null;

        if (! is_string($accessToken) || $accessToken === '' || ! is_numeric($expiresAt)) {
            return null;
        }

        $token = new self($accessToken, (int) $expiresAt);

        return $token->isExpired() ? null : $token;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function ttl(): int
    {
        return max(1, $this->expiresAt - time());
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= time();
    }

    /**
     * @return array{access_token: string, expires_at: int}
     */
    public function toCacheValue(): array
    {
        return [
            'access_token' => $this->value,
            'expires_at' => $this->expiresAt,
        ];
    }
}
