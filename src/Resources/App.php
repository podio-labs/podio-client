<?php

namespace Podio\Client\Resources;

use stdClass;

/**
 * @property-read int|null $app_id
 * @property-read stdClass|null $config
 * @property-read array<int, stdClass>|null $fields
 */
final readonly class App extends RawObjectResource {}
