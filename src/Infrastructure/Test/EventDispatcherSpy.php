<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test;

use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;

final class EventDispatcherSpy implements EventDispatcher
{
    private $dispatchedEvents = [];

    public function dispatchAll(array $events): void
    {
        $this->dispatchedEvents = array_merge($this->dispatchedEvents, $events);
    }

    public function releaseDispatchedEvents(): array
    {
        $events = $this->dispatchedEvents;
        $this->clear();

        return $events;
    }

    public function clear(): void
    {
        $this->dispatchedEvents = [];
    }
}
