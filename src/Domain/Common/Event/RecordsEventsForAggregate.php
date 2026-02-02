<?php

namespace Thinktomorrow\Trader\Domain\Common\Event;

trait RecordsEventsForAggregate
{
    private array $events = [];

    protected function recordEventForAggregate(object $event): void
    {
        $this->events[] = $event;
    }

    public function releaseEventsForAggregate(): array
    {
        $events = $this->events;

        $this->events = [];

        return $events;
    }
}
