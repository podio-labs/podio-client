<?php

use Podio\Client\Resources\App;
use Podio\Client\Resources\ItemReference;
use Podio\Client\Resources\ItemReferenceCollection;
use Podio\Client\Resources\Revision;
use Podio\Client\Resources\RevisionDifference;
use Podio\Client\Resources\RevisionDifferenceCollection;

test('the leaf resources expose their payload fields', function () {
    $app = new App((object) ['app_id' => 1, 'config' => (object) [], 'fields' => []]);
    $revision = new Revision((object) ['revision' => 7, 'created_on' => 'now']);
    $difference = new RevisionDifference((object) ['external_id' => 'title', 'type' => 'text', 'from' => [], 'to' => []]);
    $reference = new ItemReference((object) ['app' => (object) ['app_id' => 2], 'items' => []]);

    expect($app->app_id)->toBe(1)
        ->and($app->fields)->toBe([])
        ->and($revision->revision)->toBe(7)
        ->and($difference->external_id)->toBe('title')
        ->and($reference->app->app_id)->toBe(2)
        ->and($reference->items)->toBe([]);
});

test('the typed collections wrap their entries', function () {
    $references = new ItemReferenceCollection([(object) ['app' => (object) ['app_id' => 2]]]);
    $differences = new RevisionDifferenceCollection([(object) ['external_id' => 'title']]);

    expect($references->first())->toBeInstanceOf(ItemReference::class)
        ->and($references->all()[0]->app->app_id)->toBe(2)
        ->and($differences->first())->toBeInstanceOf(RevisionDifference::class)
        ->and($differences->all()[0]->external_id)->toBe('title');
});
