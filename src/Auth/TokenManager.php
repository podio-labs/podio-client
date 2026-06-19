<?php

namespace Podio\Client\Auth;

use Podio\Client\Http\Transporter;
use Psr\SimpleCache\CacheInterface;

final class TokenManager
{
    private ?AccessToken $accessToken = null;

    public function __construct(
        private readonly PodioCredentials $credentials,
        private readonly Transporter $transporter,
        private readonly ?CacheInterface $cache,
        private readonly string $cacheKey,
    ) {}

    public function ensure(): string
    {
        $accessToken = $this->cachedAccessToken();

        if ($accessToken === null) {
            $accessToken = $this->authenticateWithPassword();

            $this->store($accessToken);
        }

        return $accessToken->value();
    }

    public function refresh(): string
    {
        $this->forget();

        $accessToken = $this->authenticateWithPassword();

        $this->store($accessToken);

        return $accessToken->value();
    }

    private function authenticateWithPassword(): AccessToken
    {
        $response = $this->transporter->response($this->transporter->send('POST', '/oauth/token', [
            'form_params' => [
                'grant_type' => 'password',
                'username' => $this->credentials->username,
                'password' => $this->credentials->password,
                'client_id' => $this->credentials->clientId,
                'client_secret' => $this->credentials->clientSecret,
            ],
        ]));

        return AccessToken::fromOAuthResponse($response->body());
    }

    private function cachedAccessToken(): ?AccessToken
    {
        if ($this->accessToken !== null && ! $this->accessToken->isExpired()) {
            return $this->accessToken;
        }

        $this->accessToken = AccessToken::fromCacheValue($this->cache?->get($this->cacheKey));

        return $this->accessToken;
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

    private function forget(): void
    {
        $this->accessToken = null;

        $this->cache?->delete($this->cacheKey);
    }
}
