<?php

namespace Podio\Client\Resources;

use stdClass;

/**
 * @property-read int|null $org_id
 * @property-read string|null $name
 * @property-read string|null $url
 * @property-read string|null $url_label
 * @property-read array<int, stdClass>|null $spaces
 */
final readonly class Organization extends RawObjectResource
{
    public function spaces(): SpaceCollection
    {
        return new SpaceCollection($this->spaces);
    }
}
