<?php

namespace Podio\Client\Endpoints;

use Closure;
use Podio\Client\PodioClient;
use Podio\Client\RequestOptions;
use Podio\Client\RetryPolicy;
use Throwable;

abstract class BaseEndpoint
{
    final public function __construct(
        protected readonly PodioClient $client,
        protected readonly RequestOptions $options = new RequestOptions,
    ) {}

    public function withFields(?string $fields): static
    {
        return $this->withOptions($this->options->withFields($fields));
    }

    /**
     * @param  array<int, int>|int|RetryPolicy  $times
     * @param  (Closure(int, Throwable): int)|int  $sleepMilliseconds
     * @param  (callable(Throwable): bool)|null  $when
     */
    public function withRetry(array|int|RetryPolicy $times, Closure|int $sleepMilliseconds = 0, ?callable $when = null): static
    {
        $policy = $times instanceof RetryPolicy
            ? $times
            : RetryPolicy::make($times, $sleepMilliseconds, $when);

        return $this->withOptions($this->options->withRetry($policy));
    }

    protected function withOptions(RequestOptions $options): static
    {
        return new static($this->client, $options);
    }

    protected function path(string|int $value): string
    {
        return rawurlencode((string) $value);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function request(string $method, string $uri, array $options = []): mixed
    {
        return $this->retryPolicy()->run(
            fn (): mixed => $this->client->send($method, $uri, $this->applyOptions($options))->body(),
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function applyOptions(array $options): array
    {
        $query = isset($options['query']) && is_array($options['query']) ? $options['query'] : [];

        if ($this->options->fields !== null) {
            $query = ['fields' => $this->options->fields, ...$query];
        }

        if ($query !== []) {
            $options['query'] = $query;
        }

        return $options;
    }

    private function retryPolicy(): RetryPolicy
    {
        return $this->options->retry ?? new RetryPolicy;
    }
}
