<?php

namespace Podio\Client\Resources;

use Generator;
use IteratorAggregate;
use JsonMachine\Items;
use stdClass;

/**
 * @implements IteratorAggregate<int, Item>
 */
final readonly class LazyItemCollection implements IteratorAggregate
{
    public function __construct(private string $body) {}

    /**
     * @return Generator<int, Item>
     */
    public function getIterator(): Generator
    {
        foreach ($this->raw() as $raw) {
            yield new Item($raw);
        }
    }

    /**
     * @return Generator<int, stdClass>
     */
    public function raw(): Generator
    {
        yield from Items::fromString($this->body, ['pointer' => '/items']);
    }
}
