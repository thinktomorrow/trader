<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Cart\Ports;

use Illuminate\Contracts\Container\Container;
use Thinktomorrow\Trader\Cart\Domain\CartRepository;
use Thinktomorrow\Trader\Order\Domain\Exceptions\OrderNotInCartState;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Order\Domain\OrderReference;
use Thinktomorrow\Trader\Order\Domain\OrderRepository;

class DbCartRepository implements CartRepository
{
    private Container $container;
    private CartFactory $cartFactory;
    private OrderRepository $orderRepository;

    public function __construct(Container $container, CartFactory $cartFactory, OrderRepository $orderRepository)
    {
        $this->container = $container;
        $this->cartFactory = $cartFactory;
        $this->orderRepository = $orderRepository;
    }

    public function existsByReference(OrderReference $orderReference): bool
    {
        return $this->orderRepository->existsByReference($orderReference);
    }

    public function findByReference(OrderReference $orderReference): Order
    {
        $order = $this->orderRepository->findByReference($orderReference);

        return $this->cartFactory->create($order);
    }

    /**
     * @param Order $order
     * @throws OrderNotInCartState
     */
    public function save(Order $order): void
    {
        $this->assertValidCartState($order);

        $this->orderRepository->save($order);
    }

    public function emptyCart(OrderReference $orderReference): Order
    {
        return $this->orderRepository->emptyOrder($orderReference);
    }

    public function nextReference(): OrderReference
    {
        return $this->orderRepository->nextReference();
    }

    /**
     * @param Order $order
     * @throws OrderNotInCartState
     */
    private function assertValidCartState(Order $order): void
    {
        $orderState = OrderState::fromObject($order);
        $orderState->assertCartState();
    }
}
