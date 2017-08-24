<?php

namespace Thinktomorrow\Trader\Orders\Application\Reads\Cart;

use Illuminate\Contracts\Container\Container;
use Thinktomorrow\Trader\Common\Application\ResolvesFromContainer;
use Thinktomorrow\Trader\Discounts\Application\Reads\Discount as DiscountPresenter;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Orders\Domain\OrderRepository;

/**
 * Assembling an order will not refresh/update the given data.
 * It maintains the state when order was last stored to db.
 *
 * @package Thinktomorrow\Trader\Orders\Application
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
     * @param $orderId
     * @return MerchantOrder
     */
    public function create($orderId)
    {
        $order = $this->orderRepository->find(OrderId::fromInteger($orderId));

        $cart = $this->resolve(Cart::class, $order);

        //$this->assembleItems($order, $orderId);
        //$this->assembleAppliedDiscounts($order, $orderId);

        // TODO: add applied shipment and payment
        // TODO: add applied Tax rule

        return $cart;
    }

    private function assembleItems(MerchantOrder $order, $orderId)
    {
        $itemCollection = $this->orderRepository->getItemValues(OrderId::fromInteger($orderId));
        $items = [];

        foreach ($itemCollection as $id => $itemValues) {
            $items[$id] = $this->resolve(MerchantItem::class);
            foreach ($itemValues as $key => $value) {
                $items[$id]->{$key} = $value;
            }
        }

        $order->items = $items;
    }

    private function assembleAppliedDiscounts(MerchantOrder $order, $orderId)
    {
        $discountCollection = $this->orderRepository->getAppliedDiscounts(OrderId::fromInteger($orderId));
        $discounts = [];

        foreach ($discountCollection as $id => $discountValues) {
            $discounts[$id] = $this->resolve(DiscountPresenter::class);
            foreach ($discountValues as $key => $value) {
                $discounts[$id]->{$key} = $value;
            }
        }

        $order->discounts = $discounts;
    }
}
