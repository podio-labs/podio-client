<?php

namespace Podio\Client\Resources;

use stdClass;

/**
 * @property-read string|null $external_id
 * @property-read string|null $type
 * @property-read array<int, stdClass>|null $from
 * @property-read array<int, stdClass>|null $to
 */
final readonly class RevisionDifference extends RawObjectResource {}
