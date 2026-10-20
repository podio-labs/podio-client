<?php

namespace Podio\Client\Resources;

use stdClass;

/**
 * @extends ResourceCollection<Organization>
 */
final readonly class OrganizationCollection extends ResourceCollection
{
    protected function wrap(stdClass $raw): Organization
    {
        return new Organization($raw);
    }
}
