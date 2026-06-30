<?php

namespace Podio\Client\Auth\Grants;

final readonly class AppGrant implements Grant
{
    public function __construct(
        private int $appId,
        private string $appToken,
    ) {}

    public function parameters(): array
    {
        return [
            'grant_type' => 'app',
            'app_id' => $this->appId,
            'app_token' => $this->appToken,
        ];
    }
}
