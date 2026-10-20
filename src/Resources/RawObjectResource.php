<?php

namespace Podio\Client\Resources;

use stdClass;

abstract readonly class RawObjectResource
{
    public function __construct(private stdClass $raw) {}

    public function raw(): stdClass
    {
        return $this->raw;
    }

    public function __get(string $name): mixed
    {
        return $this->raw->{$name} ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->raw->{$name});
    }
}
