<?php

namespace Podio\Client\Resources;

use stdClass;

/**
 * @extends ResourceCollection<ItemReference>
 */
final readonly class ItemReferenceCollection extends ResourceCollection
{
    protected function wrap(stdClass $raw): ItemReference
    {
        return new ItemReference($raw);
    }
}
