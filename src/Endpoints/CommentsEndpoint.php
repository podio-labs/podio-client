<?php

namespace Podio\Client\Endpoints;

final class CommentsEndpoint extends BaseEndpoint
{
    /**
     * @link https://developers.podio.com/doc/comments/add-comment-to-object-22340
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $params
     */
    public function create(string $refType, int $refId, array $data, array $params = []): object
    {
        return $this->request('POST', '/comment/' . $this->path($refType) . '/' . $refId, ['json' => $data, 'query' => $params]);
    }
}
