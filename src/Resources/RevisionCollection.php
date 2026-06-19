<?php

namespace Podio\Client\Resources;

/**
 * @extends ResourceCollection<Revision>
 */
final readonly class RevisionCollection extends ResourceCollection
{
    protected function wrap(object $raw): Revision
    {
        return new Revision($raw);
    }
}
