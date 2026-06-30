<?php

namespace Podio\Client\Endpoints;

use Podio\Client\Resources\App;
use Podio\Client\Resources\AppCollection;

final class AppsEndpoint extends BaseEndpoint
{
    /**
     * @link https://developers.podio.com/doc/applications/get-app-22349
     */
    public function get(int $appId): App
    {
        return new App($this->request('GET', '/app/' . $appId));
    }

    /**
     * @link https://developers.podio.com/doc/applications/get-apps-by-space-22478
     */
    public function getForSpace(int $spaceId): AppCollection
    {
        return new AppCollection($this->request('GET', '/app/space/' . $spaceId . '/'));
    }
}
