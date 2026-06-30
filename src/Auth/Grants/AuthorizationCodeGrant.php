<?php

namespace Podio\Client\Auth\Grants;

final readonly class AuthorizationCodeGrant implements Grant
{
    public function __construct(
        private string $code,
        private string $redirectUri,
    ) {}

    public function parameters(): array
    {
        return [
            'grant_type' => 'authorization_code',
            'code' => $this->code,
            'redirect_uri' => $this->redirectUri,
        ];
    }
}
