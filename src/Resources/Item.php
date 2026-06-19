<?php

namespace Podio\Client\Resources;

use Podio\Client\Exceptions\MissingResourceFieldException;

/**
 * @property-read int|null $item_id
 * @property-read App $app
 * @property-read int|null $revision
 * @property-read Revision $current_revision
 * @property-read RevisionCollection $revisions
 */
final readonly class Item extends RawObjectResource
{
    public function __get(string $name): mixed
    {
        $value = parent::__get($name);

        return match ($name) {
            'app' => is_object($value) ? new App($value) : throw new MissingResourceFieldException('item', 'app'),
            'current_revision' => is_object($value) ? new Revision($value) : throw new MissingResourceFieldException('item', 'current_revision'),
            'revisions' => is_array($value) ? new RevisionCollection($value) : throw new MissingResourceFieldException('item', 'revisions'),
            default => $value,
        };
    }
}
