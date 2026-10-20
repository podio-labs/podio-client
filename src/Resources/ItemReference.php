<?php

namespace Podio\Client\Resources;

use stdClass;

/**
 * @property-read stdClass|null $app
 * @property-read array<int, stdClass>|null $items
 */
final readonly class ItemReference extends RawObjectResource {}
