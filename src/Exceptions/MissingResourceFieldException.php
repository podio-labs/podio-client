<?php

namespace Podio\Client\Exceptions;

use RuntimeException;

final class MissingResourceFieldException extends RuntimeException
{
    public function __construct(string $resource, string $field)
    {
        parent::__construct("The '{$field}' field is not present in the '{$resource}' payload. Request it explicitly when fetching the resource.");
    }
}
