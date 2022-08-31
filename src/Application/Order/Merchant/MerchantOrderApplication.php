<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\Merchant;

use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Order\Log\LogEntry;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;

class MerchantOrderApplication
{
    private OrderRepository $orderRepository;
    private EventDispatcher $eventDispatcher;

    public function __construct(OrderRepository $orderRepository, EventDispatcher $eventDispatcher)
    {
        $this->orderRepository = $orderRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function addLogEntry(AddLogEntry $command): void
    {
        $order = $this->orderRepository->find($command->getOrderId());

        $order->addLogEntry(LogEntry::create(
            $this->orderRepository->nextLogEntryReference(),
            $command->getEvent(),
            new \DateTime(),
            $command->getData(),
        ));

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function changeShippingData(ChangeShippingData $command): void
    {
        $order = $this->orderRepository->find($command->getOrderId());

        $shipping = $order->findShipping($command->getShippingId());
        $shipping->addData($command->getData());

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }
}
