<?php

namespace Podio\Client;

use Podio\Client\Auth\AccessToken;
use Podio\Client\Auth\TokenManager;
use Podio\Client\Endpoints\AppsEndpoint;
use Podio\Client\Endpoints\CommentsEndpoint;
use Podio\Client\Endpoints\EmbedEndpoint;
use Podio\Client\Endpoints\FilesEndpoint;
use Podio\Client\Endpoints\HooksEndpoint;
use Podio\Client\Endpoints\ItemsEndpoint;
use Podio\Client\Endpoints\OrganizationsEndpoint;
use Podio\Client\Endpoints\SearchEndpoint;
use Podio\Client\Endpoints\SpacesEndpoint;
use Podio\Client\Exceptions\PodioAuthenticationException;
use Podio\Client\Http\Transporter;

final class PodioClient
{
    private function __construct(
        private readonly Transporter $transporter,
        private readonly TokenManager $tokens,
    ) {}

    public static function factory(): Factory
    {
        return new Factory(static fn (mixed ...$args): self => new self(...$args));
    }

    public function organizations(): OrganizationsEndpoint
    {
        return new OrganizationsEndpoint($this);
    }

    public function spaces(): SpacesEndpoint
    {
        return new SpacesEndpoint($this);
    }

    public function apps(): AppsEndpoint
    {
        return new AppsEndpoint($this);
    }

    public function items(): ItemsEndpoint
    {
        return new ItemsEndpoint($this);
    }

    public function hooks(): HooksEndpoint
    {
        return new HooksEndpoint($this);
    }

    public function files(): FilesEndpoint
    {
        return new FilesEndpoint($this);
    }

    public function comments(): CommentsEndpoint
    {
        return new CommentsEndpoint($this);
    }

    public function search(): SearchEndpoint
    {
        return new SearchEndpoint($this);
    }

    public function embed(): EmbedEndpoint
    {
        return new EmbedEndpoint($this);
    }

    public function rateLimit(): RateLimitSnapshot
    {
        return $this->transporter->rateLimit()->snapshot();
    }

    public function authenticate(): AccessToken
    {
        $this->tokens->ensure();

        return $this->tokens->current() ?? throw new PodioAuthenticationException('Podio authentication did not yield an access token.');
    }

    public function token(): ?AccessToken
    {
        return $this->tokens->current();
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function send(string $method, string $uri, array $options = []): PodioResponse
    {
        $response = $this->transporter->send($method, $uri, $options, $this->tokens->ensure());

        if ($this->transporter->isExpiredTokenResponse($response)) {
            $response = $this->transporter->send($method, $uri, $options, $this->tokens->refresh());
        }

        return $this->transporter->response($response, (bool) ($options['raw'] ?? false));
    }
}
