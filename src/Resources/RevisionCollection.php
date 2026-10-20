<?php

namespace Podio\Client\Resources;

use stdClass;

/**
 * @extends ResourceCollection<Revision>
 */
final readonly class RevisionCollection extends ResourceCollection
{
    protected function wrap(stdClass $raw): Revision
    {
        return new Revision($raw);
    }
}
