<?php

use Podio\Client\Exceptions\PodioRequestException;
use Podio\Client\Resources\App;
use Podio\Client\Resources\AppCollection;
use Podio\Client\Resources\Item;
use Podio\Client\Resources\ItemCollection;
use Podio\Client\Resources\ItemReferenceCollection;
use Podio\Client\Resources\Organization;
use Podio\Client\Resources\OrganizationCollection;
use Podio\Client\Resources\RevisionCollection;
use Podio\Client\Resources\RevisionDifferenceCollection;
use Podio\Client\Resources\Space;
use Podio\Client\Resources\SpaceCollection;
use Podio\Client\RetryPolicy;
use Podio\Client\Tests\Support\FailingStreamWrapper;

test('apps()->get fetches an app', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '{"app_id":1,"fields":[]}')]);

    $app = $client->apps()->get(1);

    expect($app)->toBeInstanceOf(App::class)
        ->and($app->app_id)->toBe(1)
        ->and($http->requests[0]->getMethod())->toBe('GET')
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/app/1');
});

test('organizations()->getAll returns a collection with embedded spaces', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '[{"org_id":1,"name":"Acme","spaces":[{"space_id":10,"name":"Sales"},{"space_id":11,"name":"Ops"}]}]')]);

    $orgs = $client->organizations()->getAll();

    expect($orgs)->toBeInstanceOf(OrganizationCollection::class)
        ->and($orgs)->toHaveCount(1)
        ->and($orgs->first())->toBeInstanceOf(Organization::class)
        ->and($orgs->first()->org_id)->toBe(1)
        ->and($orgs->first()->name)->toBe('Acme')
        ->and($http->requests[0]->getMethod())->toBe('GET')
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/org/');

    $spaces = $orgs->first()->spaces();

    expect($spaces)->toBeInstanceOf(SpaceCollection::class)
        ->and($spaces)->toHaveCount(2)
        ->and($spaces->all()[0])->toBeInstanceOf(Space::class)
        ->and($spaces->all()[0]->space_id)->toBe(10)
        ->and($spaces->pluck('name'))->toBe(['Sales', 'Ops']);
});

test('organizations()->get fetches a single organization', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '{"org_id":7,"name":"Globex"}')]);

    $org = $client->organizations()->get(7);

    expect($org)->toBeInstanceOf(Organization::class)
        ->and($org->org_id)->toBe(7)
        ->and($org->name)->toBe('Globex')
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/org/7');
});

test('an organization without embedded spaces yields an empty space collection', function () {
    [$client] = podioClientWith([httpResponse(200, [], '{"org_id":7,"name":"Globex"}')]);

    $spaces = $client->organizations()->get(7)->spaces();

    expect($spaces)->toBeInstanceOf(SpaceCollection::class)
        ->and($spaces)->toHaveCount(0)
        ->and($spaces->all())->toBe([]);
});

test('spaces()->get fetches a single space', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '{"space_id":10,"org_id":1,"name":"Sales"}')]);

    $space = $client->spaces()->get(10);

    expect($space)->toBeInstanceOf(Space::class)
        ->and($space->space_id)->toBe(10)
        ->and($space->org_id)->toBe(1)
        ->and($space->name)->toBe('Sales')
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/space/10');
});

test('apps()->getForSpace returns the apps in a space', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '[{"app_id":100,"config":{"name":"Customers"}},{"app_id":101}]')]);

    $apps = $client->apps()->getForSpace(10);

    expect($apps)->toBeInstanceOf(AppCollection::class)
        ->and($apps)->toHaveCount(2)
        ->and($apps->all()[0])->toBeInstanceOf(App::class)
        ->and($apps->all()[0]->app_id)->toBe(100)
        ->and($apps->pluck('app_id'))->toBe([100, 101])
        ->and($http->requests[0]->getMethod())->toBe('GET')
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/app/space/10/');
});

test('items()->get fetches an item', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '{"item_id":5}')]);

    expect($client->items()->get(5))->toBeInstanceOf(Item::class)
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/item/5');
});

test('items()->getCount reads the envelope total with a minimal filter', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '{"items":[],"total":42}')]);

    expect($client->items()->getCount(9))->toBe(42)
        ->and($http->requests[0]->getMethod())->toBe('POST')
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/item/app/9/filter/')
        ->and((string) $http->requests[0]->getBody())->toContain('"limit":1')
        ->and((string) $http->requests[0]->getBody())->toContain('"offset":0');
});

test('items()->filter returns an item collection', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '{"items":[{"item_id":1}],"total":1}')]);

    $items = $client->items()->filter(9, ['limit' => 5]);

    expect($items)->toBeInstanceOf(ItemCollection::class)
        ->and($items->total())->toBe(1)
        ->and((string) $http->requests[0]->getBody())->toContain('"limit":5');
});

test('items()->getReferences returns a reference collection', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '[{"app":{"app_id":2},"items":[]}]')]);

    expect($client->items()->getReferences(5))->toBeInstanceOf(ItemReferenceCollection::class)
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/item/5/reference/');
});

test('items()->getRevisions returns a revision collection', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '[{"revision":7}]')]);

    expect($client->items()->getRevisions(5))->toBeInstanceOf(RevisionCollection::class)
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/item/5/revision');
});

test('items()->getRevisionDifferences returns a difference collection', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '[{"external_id":"x","type":"text"}]')]);

    expect($client->items()->getRevisionDifferences(5, 6, 7))->toBeInstanceOf(RevisionDifferenceCollection::class)
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/item/5/revision/6/7');
});

test('items()->create posts a new item', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '{"item_id":10}')]);

    expect($client->items()->create(9, ['fields' => ['title' => 'hi']]))->toBeInstanceOf(Item::class)
        ->and($http->requests[0]->getMethod())->toBe('POST')
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/item/app/9/')
        ->and((string) $http->requests[0]->getBody())->toContain('"title":"hi"');
});

test('items()->update puts an item', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '{"item_id":5}')]);

    expect($client->items()->update(5, ['fields' => ['title' => 'updated']]))->toBeInstanceOf(Item::class)
        ->and($http->requests[0]->getMethod())->toBe('PUT')
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/item/5')
        ->and((string) $http->requests[0]->getBody())->toContain('"title":"updated"');
});

test('files()->getRaw downloads raw bytes with an optional size', function () {
    [$client, $http] = podioClientWith([
        httpResponse(200, [], 'jpeg-bytes'),
        httpResponse(200, [], 'thumb-bytes'),
    ]);

    expect($client->files()->getRaw(77))->toBe('jpeg-bytes')
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/file/77/raw')
        ->and($client->files()->getRaw(77, 'large'))->toBe('thumb-bytes')
        ->and((string) $http->requests[1]->getUri())->toBe('https://api.podio.com/file/77/raw/large');
});

test('files()->upload reads a file and posts it', function () {
    $path = tempnam(sys_get_temp_dir(), 'podio');
    file_put_contents($path, 'bytes');

    [$client, $http] = podioClientWith([httpResponse(200, [], '{"file_id":2}')]);

    $file = $client->files()->upload($path, 'photo.jpg');

    unlink($path);

    expect($file->file_id)->toBe(2)
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/file/')
        ->and($http->requests[0]->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data; boundary=');
});

test('files()->upload throws when the source is not readable', function () {
    [$client] = podioClientWith([]);

    expect(fn () => $client->files()->upload('/does/not/exist.jpg', 'x.jpg'))
        ->toThrow(RuntimeException::class, 'source file is not readable');
});

test('files()->upload throws when the readable source cannot be read', function () {
    FailingStreamWrapper::register();

    [$client] = podioClientWith([]);

    set_error_handler(static fn (): bool => true);

    try {
        expect(fn () => $client->files()->upload('failread://source', 'x.jpg'))
            ->toThrow(RuntimeException::class, 'failed to read source file');
    } finally {
        restore_error_handler();
    }
});

test('files()->uploadContents posts a multipart body with both parts', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '{"file_id":3}')]);

    $file = $client->files()->uploadContents('image-bytes', 'photo.jpg', 'image/jpeg');
    $body = (string) $http->requests[0]->getBody();

    expect($file->file_id)->toBe(3)
        ->and($http->requests[0]->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data; boundary=')
        ->and($body)->toContain('name="source"')
        ->and($body)->toContain('filename="photo.jpg"')
        ->and($body)->toContain('Content-Type: image/jpeg')
        ->and($body)->toContain('name="filename"')
        ->and($body)->toContain('image-bytes');
});

test('files()->attach posts an attach request', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '{}')]);

    $client->files()->attach(77, ['item_id' => 5]);

    expect($http->requests[0]->getMethod())->toBe('POST')
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/file/77/attach');
});

test('hooks()->getForApp returns an array and guards non-array payloads', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '[{"hook_id":1}]')]);
    expect($client->hooks()->getForApp(9))->toHaveCount(1)
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/hook/app/9/');

    [$client2] = podioClientWith([httpResponse(200, [], '{}')]);
    expect($client2->hooks()->getForApp(9))->toBe([]);
});

test('hooks()->createForApp posts a hook', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '{"hook_id":1}')]);

    expect($client->hooks()->createForApp(9, ['type' => 'item.create'])->hook_id)->toBe(1)
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/hook/app/9/');
});

test('hooks()->verify, validate and delete hit the right routes', function () {
    [$client, $http] = podioClientWith([
        httpResponse(200, [], '{}'),
        httpResponse(200, [], '{}'),
        httpResponse(200, [], '{}'),
    ]);

    $client->hooks()->verify(1);
    $client->hooks()->validate(1, 'code-123');
    $client->hooks()->delete(1);

    expect((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/hook/1/verify/request')
        ->and((string) $http->requests[1]->getUri())->toBe('https://api.podio.com/hook/1/verify/validate')
        ->and((string) $http->requests[1]->getBody())->toContain('code-123')
        ->and($http->requests[2]->getMethod())->toBe('DELETE')
        ->and((string) $http->requests[2]->getUri())->toBe('https://api.podio.com/hook/1');
});

test('comments()->create posts a comment with an encoded ref type', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '{"comment_id":1}')]);

    expect($client->comments()->create('item', 5, ['value' => 'hi'])->comment_id)->toBe(1)
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/comment/item/5');
});

test('embed()->create posts an embed', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '{"embed_id":1}')]);

    expect($client->embed()->create(['url' => 'https://x.test'])->embed_id)->toBe(1)
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/embed/');
});

test('search()->inApp queries the v2 search endpoint', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '{"results":[]}')]);

    expect($client->search()->inApp(9, ['query' => 'ferrari'])->results)->toBe([])
        ->and((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/search/app/9/v2?query=ferrari');
});

test('withFields injects the fields query parameter', function () {
    [$client, $http] = podioClientWith([httpResponse(200, [], '{"items":[],"total":0}')]);

    $client->items()->withFields('items.fields(files)')->filter(9, ['limit' => 1]);

    expect((string) $http->requests[0]->getUri())->toBe('https://api.podio.com/item/app/9/filter/?fields=items.fields%28files%29');
});

test('withRetry accepts a ready-made retry policy instance', function () {
    [$client, $http] = podioClientWith([
        httpResponse(504, [], '{"error":"gateway_timeout"}'),
        httpResponse(200, [], '{"items":[],"total":0}'),
    ]);

    $policy = RetryPolicy::make([0], when: fn (Throwable $e): bool => true);

    $client->items()->withRetry($policy)->filter(9);

    expect($http->requests)->toHaveCount(2);
});

test('withRetry retries failed requests according to the policy', function () {
    [$client, $http] = podioClientWith([
        httpResponse(504, [], '{"error":"gateway_timeout"}'),
        httpResponse(504, [], '{"error":"gateway_timeout"}'),
        httpResponse(200, [], '{"items":[{"item_id":1}],"total":1}'),
    ]);

    $items = $client->items()
        ->withRetry([0, 0], when: fn (Throwable $e): bool => $e instanceof PodioRequestException && $e->statusCode() === 504)
        ->filter(9, ['limit' => 1]);

    expect($items->total())->toBe(1)
        ->and($http->requests)->toHaveCount(3);
});
