<?php

namespace Podio\Client;

use Closure;
use Throwable;

final readonly class RetryPolicy
{
    /**
     * @param  array<int, int>|int  $times
     * @param  (Closure(int, Throwable): int)|int  $sleepMilliseconds
     */
    public function __construct(
        private array|int $times = 1,
        private Closure|int $sleepMilliseconds = 0,
        private ?Closure $when = null,
    ) {}

    /**
     * @param  array<int, int>|int  $times
     * @param  (Closure(int, Throwable): int)|int  $sleepMilliseconds
     * @param  (callable(Throwable): bool)|null  $when
     */
    public static function make(array|int $times, Closure|int $sleepMilliseconds = 0, ?callable $when = null): self
    {
        return new self(
            times: $times,
            sleepMilliseconds: $sleepMilliseconds,
            when: $when === null ? null : Closure::fromCallable($when),
        );
    }

    /**
     * @template TValue
     *
     * @param  callable(int): TValue  $callback
     * @return TValue
     */
    public function run(callable $callback): mixed
    {
        $attempt = 0;
        $backoff = is_array($this->times) ? array_values($this->times) : [];
        $maxAttempts = $this->maxAttempts();

        while (true) {
            $attempt++;

            try {
                return $callback($attempt);
            } catch (Throwable $exception) {
                if ($attempt >= $maxAttempts || ! $this->shouldRetry($exception)) {
                    throw $exception;
                }

                $sleepMilliseconds = $backoff[$attempt - 1] ?? $this->sleepMilliseconds;
                $duration = $sleepMilliseconds instanceof Closure
                    ? $sleepMilliseconds($attempt, $exception)
                    : $sleepMilliseconds;

                if ($duration > 0) {
                    usleep((int) $duration * 1000);
                }
            }
        }
    }

    private function maxAttempts(): int
    {
        if (is_array($this->times)) {
            return max(1, count($this->times) + 1);
        }

        return max(1, $this->times);
    }

    private function shouldRetry(Throwable $exception): bool
    {
        return $this->when === null || ($this->when)($exception);
    }
}
