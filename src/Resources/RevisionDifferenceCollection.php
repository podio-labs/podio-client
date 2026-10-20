<?php

namespace Podio\Client\Resources;

use stdClass;

/**
 * @extends ResourceCollection<RevisionDifference>
 */
final readonly class RevisionDifferenceCollection extends ResourceCollection
{
    protected function wrap(stdClass $raw): RevisionDifference
    {
        return new RevisionDifference($raw);
    }
}
