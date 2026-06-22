# Podio Client

[![Latest Stable Version](https://img.shields.io/packagist/v/podio-labs/podio-client)](https://packagist.org/packages/podio-labs/podio-client)
[![License](https://img.shields.io/packagist/l/podio-labs/podio-client)](https://packagist.org/packages/podio-labs/podio-client)

A framework-agnostic PHP client for the Podio API, built on PSR-18 — bring your own HTTP client, everything else is wired for you.

In this example, we build a client and create an item — authentication is handled for you:

```php
use Podio\Client\PodioClient;

$podio = PodioClient::factory()
    ->withClientCredentials($clientId, $clientSecret)
    ->withPasswordAuth($username, $password)
    ->make();

$item = $podio->items()->create($appId, [
    'fields' => ['title' => 'New lead'],
]);
```

> Using Laravel? See [`podio-labs/podio-laravel`](https://github.com/podio-labs/podio-laravel).

## Installation

> Requires PHP 8.3+.

You can install the package via composer:

```bash
composer require podio-labs/podio-client
```

Ensure the [`php-http/discovery`](https://github.com/php-http/discovery) composer plugin is allowed to run, or install a [PSR-18](https://www.php-fig.org/psr/psr-18/) client manually if your project does not already have one:

```bash
composer require guzzlehttp/guzzle
```

## Usage

### Creating a client

```php
use Podio\Client\PodioClient;

$podio = PodioClient::factory()
    ->withClientCredentials($clientId, $clientSecret) // required
    ->withPasswordAuth($username, $password)          // required
    ->withBaseUrl('https://api.podio.com')            // optional
    ->withTokenCache($psr16Cache)                     // optional
    ->withHttpClient($psr18Client)                    // optional
    ->make();
```

### Endpoints

```php
// Items
$item = $podio->items()->get($itemId);
$item = $podio->items()->create($appId, ['fields' => ['title' => 'Hello']]);
$item = $podio->items()->update($itemId, ['fields' => ['title' => 'Updated']]);
$total = $podio->items()->getCount($appId);

// Files
$file = $podio->files()->upload($absolutePath, 'photo.jpg');
$podio->files()->attach($file->file_id, ['ref_type' => 'item', 'ref_id' => $itemId]);
$bytes = $podio->files()->getRaw($fileId);

// Comments & embeds
$podio->comments()->create('item', $itemId, ['value' => 'Imported from Dropbox']);
$embed = $podio->embed()->create(['url' => 'https://youtu.be/...']);

// Webhooks
$hooks = $podio->hooks()->getForApp($appId);
$hook = $podio->hooks()->createForApp($appId, ['url' => 'https://example.com/hook', 'type' => 'item.create']);
$podio->hooks()->verify($hook->hook_id);

// Apps
$app = $podio->apps()->get($appId);
```

### Raw requests

For anything not covered by an endpoint, send a request directly:

```php
$response = $podio->send('GET', '/item/123', ['raw' => true]);

$response->statusCode();
$response->body();
$response->rateLimit();
```

### Rate limit

```php
$podio->rateLimit()->limit();
$podio->rateLimit()->remaining();
```

## How it works

`PodioClient` wraps a transporter over your PSR-18 client (or one discovered via `php-http/discovery`) and a token manager that:

- authenticates with Podio's **password grant** and caches the access token when you pass a PSR-16 cache;
- refreshes the token automatically — `send()` retries once on an expired-token response.

Every endpoint (`items()`, `files()`, …) is a small typed wrapper over `send()`.

## Testing

```bash
composer test
```

To test code that uses the client without hitting Podio, inject a stub PSR-18 client:

```php
$podio = PodioClient::factory()
    ->withClientCredentials('id', 'secret')
    ->withPasswordAuth('user', 'pass')
    ->withHttpClient($stubPsr18Client)
    ->make();
```

## License

Podio Client is open-sourced software licensed under the [MIT license](LICENSE.md).
