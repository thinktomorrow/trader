<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test;

use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;

final class EventDispatcherSpy implements EventDispatcher
{
    private $dispatchedEvents = [];

    public function dispatch(array $events): void
    {
        $this->dispatchedEvents = array_merge($this->dispatchedEvents, $events);
    }

    public function getDispatchedEvents(): array
    {
        return $this->dispatchedEvents;
    }

    public function clear(): void
    {
        $this->dispatchedEvents = [];
    }
}
