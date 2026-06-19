<?php

namespace Podio\Client;

use Closure;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use InvalidArgumentException;
use Podio\Client\Auth\PodioCredentials;
use Podio\Client\Auth\TokenManager;
use Podio\Client\Http\Transporter;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\SimpleCache\CacheInterface;

final class Factory
{
    private ?string $clientId = null;

    private ?string $clientSecret = null;

    private ?string $username = null;

    private ?string $password = null;

    private string $baseUrl = 'https://api.podio.com';

    private ?CacheInterface $tokenCache = null;

    private string $tokenCacheKey = 'podio:access_token';

    private ?ClientInterface $http = null;

    private ?RequestFactoryInterface $requestFactory = null;

    private ?StreamFactoryInterface $streamFactory = null;

    private ?UriFactoryInterface $uriFactory = null;

    /**
     * @param  Closure(mixed ...$args): PodioClient  $instantiator
     */
    public function __construct(private readonly Closure $instantiator) {}

    public function withClientCredentials(string $clientId, string $clientSecret): self
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        return $this;
    }

    public function withPasswordAuth(string $username, string $password): self
    {
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    public function withBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function withTokenCache(CacheInterface $cache, string $key = 'podio:access_token'): self
    {
        $this->tokenCache = $cache;
        $this->tokenCacheKey = $key;

        return $this;
    }

    public function withHttpClient(ClientInterface $http): self
    {
        $this->http = $http;

        return $this;
    }

    public function withHttpFactories(RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory, UriFactoryInterface $uriFactory): self
    {
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->uriFactory = $uriFactory;

        return $this;
    }

    public function make(): PodioClient
    {
        if ($this->clientId === null || $this->clientSecret === null) {
            throw new InvalidArgumentException('Podio client credentials are required. Call withClientCredentials() before make().');
        }

        if ($this->username === null || $this->password === null) {
            throw new InvalidArgumentException('Podio password authentication is required. Call withPasswordAuth() before make().');
        }

        $transporter = new Transporter(
            http: $this->http ?? Psr18ClientDiscovery::find(),
            requestFactory: $this->requestFactory ?? Psr17FactoryDiscovery::findRequestFactory(),
            streamFactory: $this->streamFactory ?? Psr17FactoryDiscovery::findStreamFactory(),
            uriFactory: $this->uriFactory ?? Psr17FactoryDiscovery::findUriFactory(),
            baseUrl: $this->baseUrl,
            rateLimit: new RateLimitState,
        );

        return ($this->instantiator)(
            transporter: $transporter,
            tokens: new TokenManager(
                credentials: new PodioCredentials($this->clientId, $this->clientSecret, $this->username, $this->password),
                transporter: $transporter,
                cache: $this->tokenCache,
                cacheKey: $this->tokenCacheKey,
            ),
        );
    }
}
