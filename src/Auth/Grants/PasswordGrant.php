<?php

namespace Podio\Client\Auth\Grants;

final readonly class PasswordGrant implements Grant
{
    public function __construct(
        private string $username,
        private string $password,
    ) {}

    public function parameters(): array
    {
        return [
            'grant_type' => 'password',
            'username' => $this->username,
            'password' => $this->password,
        ];
    }
}
