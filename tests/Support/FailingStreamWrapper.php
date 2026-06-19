<?php

namespace Podio\Client\Tests\Support;

/**
 * A stream wrapper that reports a path as readable but fails on read,
 * so file_get_contents() returns false on an is_readable() path.
 */
final class FailingStreamWrapper
{
    /** @var resource|null */
    public $context;

    public static function register(string $scheme = 'failread'): void
    {
        if (! in_array($scheme, stream_get_wrappers(), true)) {
            stream_wrapper_register($scheme, self::class);
        }
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        return false;
    }

    /**
     * @return array<int|string, int>
     */
    public function url_stat(string $path, int $flags): array
    {
        return [
            'dev' => 0, 'ino' => 0, 'mode' => 0100444, 'nlink' => 1,
            'uid' => 0, 'gid' => 0, 'rdev' => 0, 'size' => 0,
            'atime' => 0, 'mtime' => 0, 'ctime' => 0, 'blksize' => -1, 'blocks' => -1,
        ];
    }
}
