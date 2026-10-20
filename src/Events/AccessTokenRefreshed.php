<?php

namespace Podio\Client\Events;

use Podio\Client\Auth\AccessToken;

final readonly class AccessTokenRefreshed
{
    public function __construct(public AccessToken $accessToken) {}
}
