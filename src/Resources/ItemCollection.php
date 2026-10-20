<?php

namespace Podio\Client\Resources;

use stdClass;

/**
 * @extends ResourceCollection<Item>
 */
final readonly class ItemCollection extends ResourceCollection
{
    public function payload(): object
    {
        return is_object($this->raw) ? $this->raw : (object) [];
    }

    /**
     * @return array<int, stdClass>
     */
    public function raw(): array
    {
        $items = $this->payload()->items ?? [];

        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, is_object(...)));
    }

    public function total(): int
    {
        $total = $this->payload()->total ?? null;

        return is_int($total) ? $total : count($this->raw());
    }

    /**
     * @param  (callable(Item): mixed)|string  $key
     */
    public function sortBy(callable|string $key, bool $descending = false): static
    {
        $payload = clone $this->payload();
        $payload->items = array_map(fn (RawObjectResource $resource): stdClass => $resource->raw(), $this->sortResources($key, $descending));

        return new self($payload);
    }

    protected function wrap(stdClass $raw): Item
    {
        return new Item($raw);
    }
}
