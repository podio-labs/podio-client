<?php

namespace Podio\Client\Endpoints;

use Podio\Client\Resources\App;

final class AppsEndpoint extends BaseEndpoint
{
    /**
     * @link https://developers.podio.com/doc/applications/get-app-22349
     */
    public function get(int $appId): App
    {
        return new App($this->request('GET', '/app/' . $appId));
    }
}
