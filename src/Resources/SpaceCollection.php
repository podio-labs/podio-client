<?php

namespace Podio\Client\Resources;

/**
 * @extends ResourceCollection<Space>
 */
final readonly class SpaceCollection extends ResourceCollection
{
    protected function wrap(object $raw): Space
    {
        return new Space($raw);
    }
}
