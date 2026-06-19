<?php

use Podio\Client\Resources\Item;
use Podio\Client\Resources\ItemCollection;

test('it reads items and metadata from the filter envelope', function () {
    $collection = new ItemCollection((object) [
        'items' => [(object) ['item_id' => 1], (object) ['item_id' => 2]],
        'total' => 2,
        'filtered' => 2,
    ]);

    expect($collection->all())->toHaveCount(2)
        ->and($collection->all()[0])->toBeInstanceOf(Item::class)
        ->and($collection->total())->toBe(2)
        ->and($collection->payload()->filtered)->toBe(2);
});

test('total falls back to the item count when the envelope omits it', function () {
    $collection = new ItemCollection((object) ['items' => [(object) ['item_id' => 1]]]);

    expect($collection->total())->toBe(1);
});

test('it degrades gracefully for a non-object payload', function () {
    $collection = new ItemCollection([]);

    expect($collection->payload())->toEqual((object) [])
        ->and($collection->raw())->toBe([])
        ->and($collection->total())->toBe(0);
});

test('it ignores non-array items in the envelope', function () {
    $collection = new ItemCollection((object) ['items' => 'not-an-array']);

    expect($collection->raw())->toBe([]);
});

test('empty exposes a zero total', function () {
    expect(ItemCollection::empty())->toHaveCount(0)
        ->and(ItemCollection::empty()->total())->toBe(0);
});

test('sortBy preserves the envelope and stays immutable', function () {
    $collection = new ItemCollection((object) [
        'items' => [(object) ['item_id' => 2], (object) ['item_id' => 1]],
        'total' => 2,
        'filtered' => 2,
    ]);

    $sorted = $collection->sortBy('item_id');

    expect(array_map(fn (Item $item): int => $item->item_id, $sorted->all()))->toBe([1, 2])
        ->and($sorted->total())->toBe(2)
        ->and($sorted->payload()->filtered)->toBe(2)
        ->and(array_map(fn (Item $item): int => $item->item_id, $collection->all()))->toBe([2, 1]);
});
