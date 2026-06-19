<?php

use Podio\Client\Resources\Revision;
use Podio\Client\Resources\RevisionCollection;

test('empty builds an empty collection', function () {
    expect(RevisionCollection::empty())->toBeInstanceOf(RevisionCollection::class)
        ->and(RevisionCollection::empty())->toHaveCount(0)
        ->and(RevisionCollection::empty()->all())->toBe([]);
});

test('raw filters out non-object entries', function () {
    $collection = new RevisionCollection([(object) ['revision' => 1], 'noise', 42]);

    expect($collection->raw())->toHaveCount(1)
        ->and($collection->count())->toBe(1);
});

test('raw returns an empty array for a non-array payload', function () {
    expect((new RevisionCollection('nonsense'))->raw())->toBe([]);
});

test('all wraps each entry into a typed resource', function () {
    $collection = new RevisionCollection([(object) ['revision' => 1], (object) ['revision' => 2]]);

    expect($collection->all())->toHaveCount(2)
        ->and($collection->all()[0])->toBeInstanceOf(Revision::class)
        ->and($collection->all()[0]->revision)->toBe(1);
});

test('first returns the wrapped first entry or null', function () {
    $collection = new RevisionCollection([(object) ['revision' => 1]]);

    expect($collection->first())->toBeInstanceOf(Revision::class)
        ->and($collection->first()->revision)->toBe(1)
        ->and(RevisionCollection::empty()->first())->toBeNull();
});

test('it is iterable and yields wrapped resources', function () {
    $collection = new RevisionCollection([(object) ['revision' => 1], (object) ['revision' => 2]]);

    $seen = [];

    foreach ($collection as $revision) {
        expect($revision)->toBeInstanceOf(Revision::class);
        $seen[] = $revision->revision;
    }

    expect($seen)->toBe([1, 2]);
});

test('sortBy sorts immutably ascending and descending', function () {
    $collection = new RevisionCollection([
        (object) ['revision' => 102],
        (object) ['revision' => 100],
        (object) ['revision' => 101],
    ]);

    $ascending = $collection->sortBy('revision');
    $descending = $collection->sortBy('revision', descending: true);

    expect($ascending)->not->toBe($collection)
        ->and($ascending->pluck('revision'))->toBe([100, 101, 102])
        ->and($descending->pluck('revision'))->toBe([102, 101, 100])
        ->and($collection->pluck('revision'))->toBe([102, 100, 101]);
});

test('sortBy accepts a callable key', function () {
    $collection = new RevisionCollection([(object) ['revision' => 2], (object) ['revision' => 1]]);

    expect($collection->sortBy(fn (Revision $revision): int => $revision->revision)->pluck('revision'))->toBe([1, 2]);
});

test('pluck supports both string keys and callables', function () {
    $collection = new RevisionCollection([
        (object) ['revision' => 1],
        (object) ['revision' => 2],
    ]);

    expect($collection->pluck('revision'))->toBe([1, 2])
        ->and($collection->pluck(fn (Revision $revision): int => $revision->revision + 100))->toBe([101, 102]);
});
