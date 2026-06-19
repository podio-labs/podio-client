<?php

use Podio\Client\Exceptions\MissingResourceFieldException;
use Podio\Client\Resources\App;
use Podio\Client\Resources\Item;
use Podio\Client\Resources\Revision;
use Podio\Client\Resources\RevisionCollection;

test('it resolves the typed nested nodes', function () {
    $item = new Item((object) [
        'item_id' => 1,
        'revision' => 3,
        'app' => (object) ['app_id' => 9],
        'current_revision' => (object) ['revision' => 3],
        'revisions' => [(object) ['revision' => 1], (object) ['revision' => 2]],
    ]);

    expect($item->item_id)->toBe(1)
        ->and($item->revision)->toBe(3)
        ->and($item->app)->toBeInstanceOf(App::class)
        ->and($item->app->app_id)->toBe(9)
        ->and($item->current_revision)->toBeInstanceOf(Revision::class)
        ->and($item->current_revision->revision)->toBe(3)
        ->and($item->revisions)->toBeInstanceOf(RevisionCollection::class)
        ->and($item->revisions)->toHaveCount(2);
});

test('scalar fields pass through untyped', function () {
    $item = new Item((object) ['item_id' => 1, 'revision' => 7]);

    expect($item->item_id)->toBe(1)
        ->and($item->revision)->toBe(7);
});

test('a missing typed node throws MissingResourceFieldException', function (string $field) {
    $item = new Item((object) ['item_id' => 1]);

    expect(fn () => $item->{$field})->toThrow(MissingResourceFieldException::class);
})->with(['app', 'current_revision', 'revisions']);

test('a malformed typed node also throws MissingResourceFieldException', function () {
    $item = new Item((object) ['app' => 'not-an-object', 'current_revision' => 5, 'revisions' => 'nope']);

    expect(fn () => $item->app)->toThrow(MissingResourceFieldException::class)
        ->and(fn () => $item->current_revision)->toThrow(MissingResourceFieldException::class)
        ->and(fn () => $item->revisions)->toThrow(MissingResourceFieldException::class);
});
