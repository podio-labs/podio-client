<?php

namespace Podio\Client\Resources;

/**
 * @extends ResourceCollection<RevisionDifference>
 */
final readonly class RevisionDifferenceCollection extends ResourceCollection
{
    protected function wrap(object $raw): RevisionDifference
    {
        return new RevisionDifference($raw);
    }
}
