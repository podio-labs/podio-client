<?php

namespace Podio\Client\Endpoints;

final class SearchEndpoint extends BaseEndpoint
{
    /**
     * @link https://developers.podio.com/doc/search/search-in-application-v2-155196220
     *
     * @param  array<string, mixed>  $params
     */
    public function inApp(int $appId, array $params): object
    {
        return $this->request('GET', '/search/app/' . $appId . '/v2', ['query' => $params]);
    }
}
