<?php

namespace Podio\Client\Endpoints;

use Podio\Client\Resources\Item;
use Podio\Client\Resources\ItemCollection;
use Podio\Client\Resources\ItemReferenceCollection;
use Podio\Client\Resources\RevisionCollection;
use Podio\Client\Resources\RevisionDifferenceCollection;

final class ItemsEndpoint extends BaseEndpoint
{
    /**
     * @link https://developers.podio.com/doc/items/get-item-22360
     */
    public function get(int $itemId): Item
    {
        return new Item($this->request('GET', '/item/' . $itemId));
    }

    /**
     * @link https://developers.podio.com/doc/items/filter-items-4496747
     *
     * @param  array<string, mixed>  $params
     */
    public function getCount(int $appId, array $params = []): int
    {
        $params = [...$params, 'limit' => 1, 'offset' => 0];

        return $this->filter($appId, $params)->total();
    }

    /**
     * @link https://developers.podio.com/doc/items/filter-items-4496747
     *
     * @param  array<string, mixed>  $params
     */
    public function filter(int $appId, array $params = []): ItemCollection
    {
        return new ItemCollection($this->request('POST', '/item/app/' . $appId . '/filter/', [
            'json' => $params,
        ]));
    }

    /**
     * @link https://developers.podio.com/doc/items/get-item-references-22439
     */
    public function getReferences(int $itemId): ItemReferenceCollection
    {
        return new ItemReferenceCollection($this->request('GET', '/item/' . $itemId . '/reference/'));
    }

    /**
     * @link https://developers.podio.com/doc/items/get-item-revisions-22372
     */
    public function getRevisions(int $itemId): RevisionCollection
    {
        return new RevisionCollection($this->request('GET', '/item/' . $itemId . '/revision'));
    }

    /**
     * @link https://developers.podio.com/doc/items/get-item-revision-difference-22374
     */
    public function getRevisionDifferences(int $itemId, int $from, int $to): RevisionDifferenceCollection
    {
        return new RevisionDifferenceCollection($this->request('GET', '/item/' . $itemId . '/revision/' . $from . '/' . $to));
    }

    /**
     * @link https://developers.podio.com/doc/items/add-new-item-22362
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $params
     */
    public function create(int $appId, array $data, array $params = []): Item
    {
        return new Item($this->request('POST', '/item/app/' . $appId . '/', [
            'json' => $data,
            'query' => $params,
        ]));
    }

    /**
     * @link https://developers.podio.com/doc/items/update-item-22363
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $itemId, array $data): Item
    {
        return new Item($this->request('PUT', '/item/' . $itemId, ['json' => $data]));
    }
}
