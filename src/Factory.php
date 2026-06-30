<?php

namespace Podio\Client;

use Closure;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use InvalidArgumentException;
use Podio\Client\Auth\AccessToken;
use Podio\Client\Auth\Grants\AppGrant;
use Podio\Client\Auth\Grants\AuthorizationCodeGrant;
use Podio\Client\Auth\Grants\Grant;
use Podio\Client\Auth\Grants\PasswordGrant;
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

    private ?Grant $grant = null;

    private ?AccessToken $accessToken = null;

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
        $this->grant = new PasswordGrant($username, $password);

        return $this;
    }

    public function withAuthorizationCodeAuth(string $code, string $redirectUri): self
    {
        $this->grant = new AuthorizationCodeGrant($code, $redirectUri);

        return $this;
    }

    public function withAppAuth(int $appId, string $appToken): self
    {
        $this->grant = new AppGrant($appId, $appToken);

        return $this;
    }

    public function withAccessToken(string $accessToken, int $expiresAt, ?string $refreshToken = null): self
    {
        $this->accessToken = AccessToken::fromValues($accessToken, $expiresAt, $refreshToken);

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

        if ($this->grant === null && $this->accessToken === null) {
            throw new InvalidArgumentException('Podio authentication is required. Call withPasswordAuth(), withAuthorizationCodeAuth(), withAppAuth(), or withAccessToken() before make().');
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
                credentials: new PodioCredentials($this->clientId, $this->clientSecret),
                transporter: $transporter,
                cache: $this->tokenCache,
                cacheKey: $this->tokenCacheKey,
                grant: $this->grant,
                accessToken: $this->accessToken,
            ),
        );
    }
}
