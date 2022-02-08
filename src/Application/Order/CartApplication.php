<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order;

use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\LineNumber;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;

final class CartApplication
{
    private ProductRepository $productRepository;
    private OrderRepository $orderRepository;
    private EventDispatcher $eventDispatcher;

    public function __construct(ProductRepository $productRepository, OrderRepository $orderRepository, EventDispatcher $eventDispatcher)
    {
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function addLine(AddLine $addLine): void
    {
        $order = $this->orderRepository->find($addLine->getOrderId());

        $order->addOrUpdateLine(
            $addLine->getLineNumber(),
            $addLine->getProductId(),
            $addLine->getQuantity()
        );

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatch($order->releaseEvents());
    }
}
