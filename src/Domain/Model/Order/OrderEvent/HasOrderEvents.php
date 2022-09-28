<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\OrderEvent;

trait HasOrderEvents
{
    /** @var OrderEvent[] */
    private array $orderEvents = [];

    public function addLogEntry(OrderEvent $orderEvent): void
    {
        $this->orderEvents[] = $orderEvent;
    }

    public function getOrderEvents(): array
    {
        return $this->orderEvents;
    }
}
