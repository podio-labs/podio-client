<?php

namespace Podio\Client\Resources;

use stdClass;

/**
 * @extends ResourceCollection<App>
 */
final readonly class AppCollection extends ResourceCollection
{
    protected function wrap(stdClass $raw): App
    {
        return new App($raw);
    }
}
