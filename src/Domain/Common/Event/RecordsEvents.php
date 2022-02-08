<?php

namespace Thinktomorrow\Trader\Domain\Common\Event;

trait RecordsEvents
{
    /** @var object[] */
    private array $events = [];

    protected function recordEvent(object $event): void
    {
        $this->events[] = $event;
    }

    public function releaseEvents(): array
    {
        $events = $this->events;

        $this->events = [];

        return $events;
    }
}
