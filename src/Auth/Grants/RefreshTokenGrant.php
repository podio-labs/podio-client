<?php

namespace Podio\Client\Auth\Grants;

final readonly class RefreshTokenGrant implements Grant
{
    public function __construct(
        private string $refreshToken,
    ) {}

    public function parameters(): array
    {
        return [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken,
        ];
    }
}
