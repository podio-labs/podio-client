<?php

namespace Podio\Client\Resources;

abstract readonly class RawObjectResource
{
    public function __construct(private object $raw) {}

    public function raw(): object
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
