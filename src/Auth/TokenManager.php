<?php

namespace Podio\Client\Auth;

use Podio\Client\Auth\Grants\Grant;
use Podio\Client\Auth\Grants\PasswordGrant;
use Podio\Client\Auth\Grants\RefreshTokenGrant;
use Podio\Client\Events\TokenRefreshed;
use Podio\Client\Exceptions\PodioAuthenticationException;
use Podio\Client\Http\Transporter;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\SimpleCache\CacheInterface;

final class TokenManager
{
    public function __construct(
        private readonly PodioCredentials $credentials,
        private readonly Transporter $transporter,
        private readonly ?CacheInterface $cache,
        private readonly string $cacheKey,
        private readonly ?Grant $grant = null,
        private ?Token $token = null,
        private readonly ?EventDispatcherInterface $events = null,
    ) {}

    public function ensure(): string
    {
        $token = $this->validToken();

        if ($token !== null) {
            return $token->accessToken();
        }

        if ($this->token?->refreshToken() !== null) {
            return $this->refresh();
        }

        $token = $this->authenticate();

        $this->store($token);

        return $token->accessToken();
    }

    public function refresh(): string
    {
        $refreshToken = $this->token?->refreshToken();

        $this->cache?->delete($this->cacheKey);

        $token = ($refreshToken !== null && ! $this->grant instanceof PasswordGrant)
            ? $this->requestToken(new RefreshTokenGrant($refreshToken))
            : $this->authenticate();

        $this->store($token);

        return $token->accessToken();
    }

    public function current(): ?Token
    {
        return $this->token;
    }

    private function authenticate(): Token
    {
        if ($this->grant === null) {
            throw new PodioAuthenticationException('Podio cannot authenticate: no grant configured and no refreshable token.');
        }

        return $this->requestToken($this->grant);
    }

    private function requestToken(Grant $grant): Token
    {
        $response = $this->transporter->response(
            $this->transporter->send('POST', '/oauth/token', ['form_params' => [
                ...$grant->parameters(),
                'client_id' => $this->credentials->clientId,
                'client_secret' => $this->credentials->clientSecret,
            ]])
        );

        return Token::fromOAuthResponse($response->body());
    }

    private function validToken(): ?Token
    {
        if ($this->token !== null && ! $this->token->isExpired()) {
            return $this->token;
        }

        $token = Token::fromCacheValue($this->cache?->get($this->cacheKey));

        if ($token !== null) {
            return $this->token = $token;
        }

        return null;
    }

    private function store(Token $token): void
    {
        $this->token = $token;

        $this->cache?->set(
            $this->cacheKey,
            $token->toCacheValue(),
            $token->ttl(),
        );

        $this->events?->dispatch(new TokenRefreshed($token));
    }
}
