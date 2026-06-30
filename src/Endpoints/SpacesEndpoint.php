<?php

namespace Podio\Client\Endpoints;

use Podio\Client\Resources\Space;

final class SpacesEndpoint extends BaseEndpoint
{
    /**
     * @link https://developers.podio.com/doc/spaces/get-space-22389
     */
    public function get(int $spaceId): Space
    {
        return new Space($this->request('GET', '/space/' . $spaceId));
    }
}
