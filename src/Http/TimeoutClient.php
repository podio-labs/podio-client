<?php

namespace Podio\Client\Http;

use Psr\Http\Client\ClientInterface;

interface TimeoutClient extends ClientInterface
{
    public function withTimeout(int $seconds): self;
}
