<?php

namespace Podio\Client\Events;

use Podio\Client\Auth\Token;

final readonly class TokenRefreshed
{
    public function __construct(public Token $token) {}
}
