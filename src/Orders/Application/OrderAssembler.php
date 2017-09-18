<?php

namespace Thinktomorrow\Trader\Orders\Application;

use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Orders\Domain\OrderRepository;

/**
 * Reconstruct order domain from persistence layer.
 * This will reapply all rules to make sure the
 * order is updated to current business logic.
 */
class OrderAssembler
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function assemble($orderId)
    {
        $order = $this->orderRepository->find(OrderId::fromInteger($orderId));

        return $order;
    }
}
