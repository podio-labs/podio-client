<?php

namespace Podio\Client\Resources;

/**
 * @extends ResourceCollection<Organization>
 */
final readonly class OrganizationCollection extends ResourceCollection
{
    protected function wrap(object $raw): Organization
    {
        return new Organization($raw);
    }
}
