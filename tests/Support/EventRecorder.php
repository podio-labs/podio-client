<?php

namespace Podio\Client\Tests\Support;

use Psr\EventDispatcher\EventDispatcherInterface;

final class EventRecorder implements EventDispatcherInterface
{
    public array $events = [];

    public function dispatch(object $event): object
    {
        $this->events[] = $event;

        return $event;
    }
}
