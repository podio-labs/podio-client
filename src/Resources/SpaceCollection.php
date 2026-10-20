<?php

namespace Podio\Client\Resources;

use stdClass;

/**
 * @extends ResourceCollection<Space>
 */
final readonly class SpaceCollection extends ResourceCollection
{
    protected function wrap(stdClass $raw): Space
    {
        return new Space($raw);
    }
}
