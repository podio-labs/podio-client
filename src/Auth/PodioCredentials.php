<?php

namespace Podio\Client\Auth;

final readonly class PodioCredentials
{
    public function __construct(
        public string $clientId,
        public string $clientSecret,
    ) {}
}
