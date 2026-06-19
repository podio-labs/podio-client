<?php

namespace Podio\Client\Endpoints;

final class EmbedEndpoint extends BaseEndpoint
{
    /**
     * @link https://developers.podio.com/doc/embeds/add-an-embed-726483
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): object
    {
        return $this->request('POST', '/embed/', ['json' => $data]);
    }
}
