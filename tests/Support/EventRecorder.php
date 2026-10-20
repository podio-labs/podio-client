<?php

namespace Podio\Client\Tests\Support;

use Psr\EventDispatcher\EventDispatcherInterface;

final class EventRecorder implements EventDispatcherInterface
{
    /**
     * @var array<int, object>
     */
    public array $events = [];

    public function dispatch(object $event): object
    {
        $this->events[] = $event;

        return $event;
    }
}
