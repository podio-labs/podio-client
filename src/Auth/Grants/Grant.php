<?php

namespace Podio\Client\Auth\Grants;

interface Grant
{
    public function parameters(): array;
}
