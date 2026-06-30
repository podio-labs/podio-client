<?php

namespace Podio\Client\Auth;

use Podio\Client\Auth\Grants\Grant;
use Podio\Client\Auth\Grants\PasswordGrant;
use Podio\Client\Auth\Grants\RefreshTokenGrant;
use Podio\Client\Exceptions\PodioAuthenticationException;
use Podio\Client\Http\Transporter;
use Psr\SimpleCache\CacheInterface;

final class TokenManager
{
    public function __construct(
        private readonly PodioCredentials $credentials,
        private readonly Transporter $transporter,
        private readonly ?CacheInterface $cache,
        private readonly string $cacheKey,
        private readonly ?Grant $grant = null,
        private ?AccessToken $accessToken = null,
    ) {}

    public function ensure(): string
    {
        $accessToken = $this->validToken();

        if ($accessToken !== null) {
            return $accessToken->value();
        }

        if ($this->accessToken?->refreshToken() !== null) {
            return $this->refresh();
        }

        $accessToken = $this->authenticate();

        $this->store($accessToken);

        return $accessToken->value();
    }

    public function refresh(): string
    {
        $refreshToken = $this->accessToken?->refreshToken();

        $this->cache?->delete($this->cacheKey);

        $accessToken = ($refreshToken !== null && ! $this->grant instanceof PasswordGrant)
            ? $this->requestToken(new RefreshTokenGrant($refreshToken))
            : $this->authenticate();

        $this->store($accessToken);

        return $accessToken->value();
    }

    public function current(): ?AccessToken
    {
        return $this->accessToken;
    }

    private function authenticate(): AccessToken
    {
        if ($this->grant === null) {
            throw new PodioAuthenticationException('Podio cannot authenticate: no grant configured and no refreshable token.');
        }

        return $this->requestToken($this->grant);
    }

    private function requestToken(Grant $grant): AccessToken
    {
        $response = $this->transporter->response(
            $this->transporter->send('POST', '/oauth/token', ['form_params' => [
                ...$grant->parameters(),
                'client_id' => $this->credentials->clientId,
                'client_secret' => $this->credentials->clientSecret,
            ]])
        );

        return AccessToken::fromOAuthResponse($response->body());
    }

    private function validToken(): ?AccessToken
    {
        if ($this->accessToken !== null && ! $this->accessToken->isExpired()) {
            return $this->accessToken;
        }

        $accessToken = AccessToken::fromCacheValue($this->cache?->get($this->cacheKey));

        if ($accessToken !== null) {
            return $this->accessToken = $accessToken;
        }

        return null;
    }

    private function store(AccessToken $accessToken): void
    {
        $this->accessToken = $accessToken;

        $this->cache?->set(
            $this->cacheKey,
            $accessToken->toCacheValue(),
            $accessToken->ttl(),
        );
    }
}
