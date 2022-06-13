<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order;

use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;

final class OrderApplication
{
    private OrderRepository $orderRepository;
    private EventDispatcher $eventDispatcher;

    public function __construct(OrderRepository $orderRepository, EventDispatcher $eventDispatcher)
    {
        $this->orderRepository = $orderRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function createOrder(CreateOrder $createOrder): void
    {
        $orderId = $this->orderRepository->nextReference();

        $order = Order::create($orderId, $createOrder->getCustomerId());

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }
}
