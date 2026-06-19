<?php

namespace Podio\Client\Resources;

/**
 * @extends ResourceCollection<ItemReference>
 */
final readonly class ItemReferenceCollection extends ResourceCollection
{
    protected function wrap(object $raw): ItemReference
    {
        return new ItemReference($raw);
    }
}
