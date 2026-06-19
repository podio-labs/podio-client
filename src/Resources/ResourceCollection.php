<?php

namespace Podio\Client\Resources;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @template T of RawObjectResource
 *
 * @implements IteratorAggregate<int, T>
 */
abstract readonly class ResourceCollection implements Countable, IteratorAggregate
{
    final public function __construct(protected mixed $raw) {}

    public static function empty(): static
    {
        return new static([]);
    }

    /**
     * @return array<int, object>
     */
    public function raw(): array
    {
        if (! is_array($this->raw)) {
            return [];
        }

        return array_values(array_filter($this->raw, is_object(...)));
    }

    /**
     * @return array<int, T>
     */
    public function all(): array
    {
        return array_map($this->wrap(...), $this->raw());
    }

    /**
     * @return T|null
     */
    public function first(): ?RawObjectResource
    {
        $first = $this->raw()[0] ?? null;

        return $first === null ? null : $this->wrap($first);
    }

    /**
     * @param  (callable(T): mixed)|string  $key
     */
    public function sortBy(callable|string $key, bool $descending = false): static
    {
        return new static(array_map(fn (RawObjectResource $resource): object => $resource->raw(), $this->sortResources($key, $descending)));
    }

    /**
     * @param  (callable(T): mixed)|string  $key
     * @return array<int, mixed>
     */
    public function pluck(callable|string $key): array
    {
        return array_map($this->valueRetriever($key), $this->all());
    }

    public function count(): int
    {
        return count($this->raw());
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }

    /**
     * @param  (callable(T): mixed)|string  $key
     * @return callable(T): mixed
     */
    protected function valueRetriever(callable|string $key): callable
    {
        return is_string($key) ? fn (RawObjectResource $resource): mixed => $resource->{$key} : $key;
    }

    /**
     * @param  (callable(T): mixed)|string  $key
     * @return array<int, T>
     */
    protected function sortResources(callable|string $key, bool $descending): array
    {
        $callback = $this->valueRetriever($key);

        $resources = $this->all();

        usort($resources, fn (RawObjectResource $left, RawObjectResource $right): int => $callback($left) <=> $callback($right));

        return $descending ? array_reverse($resources) : $resources;
    }

    /**
     * @return T
     */
    abstract protected function wrap(object $raw): RawObjectResource;
}
