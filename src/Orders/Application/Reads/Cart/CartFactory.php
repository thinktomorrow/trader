<?php

namespace Thinktomorrow\Trader\Orders\Application\Reads\Cart;

use Illuminate\Contracts\Container\Container;
use Thinktomorrow\Trader\Common\Application\ResolvesFromContainer;
use Thinktomorrow\Trader\Discounts\Application\Reads\Discount as DiscountPresenter;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Orders\Domain\OrderRepository;

/**
 * Assembling an order will not refresh/update the given data.
 * It maintains the state when order was last stored to db.
 */
class CartFactory
{
    use ResolvesFromContainer;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var Container
     */
    private $container;

    public function __construct(OrderRepository $orderRepository, Container $container)
    {
        $this->orderRepository = $orderRepository;
        $this->container = $container;
    }

    /**
     * This data (raw) will be presented as a simple read-only DTO.
     *
     * @param Order $order
     * @return Cart
     */
    public function create(Order $order): Cart
    {
        return $this->resolve(Cart::class, $order);
    }
}
