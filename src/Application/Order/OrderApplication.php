<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order;

use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\OrderStateMachine;

final class OrderApplication
{
    private OrderRepository $orderRepository;
    private OrderStateMachine $orderStateMachine;
    private EventDispatcher $eventDispatcher;

    public function __construct(OrderRepository $orderRepository, OrderStateMachine $orderStateMachine, EventDispatcher $eventDispatcher)
    {
        $this->orderRepository = $orderRepository;
        $this->orderStateMachine = $orderStateMachine;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function payOrder(PayOrder $command): void
    {
        $order = $this->orderRepository->findForCart($command->getOrderId());

        $this->orderStateMachine->apply($order, 'pay');

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function shipOrder(PayOrder $command): void
    {
        $order = $this->orderRepository->findForCart($command->getOrderId());

        $this->orderStateMachine->apply($order, 'pay');

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }
}
