<?php

namespace Podio\Client\Resources;

/**
 * @property-read int|null $user_id
 * @property-read string|null $mail
 * @property-read string|null $status
 * @property-read string|null $locale
 * @property-read string|null $timezone
 */
final readonly class User extends RawObjectResource {}
