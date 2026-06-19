<?php

use Podio\Client\Resources\Revision;

test('it exposes the raw payload and reads scalar fields through __get', function () {
    $revision = new Revision((object) ['revision' => 5, 'created_on' => '2026-05-21T10:00:00Z']);

    expect($revision->raw())->toEqual((object) ['revision' => 5, 'created_on' => '2026-05-21T10:00:00Z'])
        ->and($revision->revision)->toBe(5)
        ->and($revision->created_on)->toBe('2026-05-21T10:00:00Z');
});

test('it returns null for missing fields', function () {
    $revision = new Revision((object) ['revision' => 5]);

    expect($revision->created_on)->toBeNull()
        ->and($revision->anything ?? 'default')->toBe('default');
});

test('__isset reflects the presence of a field', function () {
    $revision = new Revision((object) ['revision' => 5]);

    expect(isset($revision->revision))->toBeTrue()
        ->and(isset($revision->created_on))->toBeFalse();
});
