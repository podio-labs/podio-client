<?php

namespace Podio\Client\Resources;

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
     * @return array<int, object>
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
        $payload->items = array_map(fn (RawObjectResource $resource): object => $resource->raw(), $this->sortResources($key, $descending));

        return new self($payload);
    }

    protected function wrap(object $raw): Item
    {
        return new Item($raw);
    }
}
