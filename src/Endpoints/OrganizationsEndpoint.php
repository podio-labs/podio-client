<?php

namespace Podio\Client\Endpoints;

use Podio\Client\Resources\Organization;
use Podio\Client\Resources\OrganizationCollection;

final class OrganizationsEndpoint extends BaseEndpoint
{
    /**
     * @link https://developers.podio.com/doc/organizations/get-organizations-22344
     */
    public function getAll(): OrganizationCollection
    {
        return new OrganizationCollection($this->request('GET', '/org/'));
    }

    /**
     * @link https://developers.podio.com/doc/organizations/get-organization-22383
     */
    public function get(int $orgId): Organization
    {
        return new Organization($this->request('GET', '/org/' . $orgId));
    }
}
