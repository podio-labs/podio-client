<?php

namespace Podio\Client\Resources;

/**
 * @extends ResourceCollection<App>
 */
final readonly class AppCollection extends ResourceCollection
{
    protected function wrap(object $raw): App
    {
        return new App($raw);
    }
}
