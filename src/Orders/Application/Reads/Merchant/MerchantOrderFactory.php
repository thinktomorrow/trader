<?php

namespace Thinktomorrow\Trader\Orders\Application\Reads\Merchant;

use Illuminate\Contracts\Container\Container;
use Money\Money;
use Thinktomorrow\Trader\Common\Application\ResolvesFromContainer;
use Thinktomorrow\Trader\Discounts\Application\Reads\Discount as DiscountPresenter;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Orders\Domain\OrderRepository;

/**
 * Assembling an order will not refresh/update the given data.
 * It maintains the state when order was last stored to db.
 */
class MerchantOrderFactory
{
    use ResolvesFromContainer;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var PSR-11 ContainerInterface
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
     * @param MerchantOrderResource $order
     *
     * @return MerchantOrder
     */
    public function create(MerchantOrderResource $order)
    {
        $values = $order->merchantValues();

        $merchantOrder = $this->resolve(MerchantOrder::class);
        foreach ($values as $key => $value) {
            $merchantOrder->{$key} = $value;
        }

        $this->assembleItems($merchantOrder, $order->merchantItemValues());
        $this->assembleAppliedDiscounts($merchantOrder, $order->merchantDiscountValues());

        // TODO: add applied shipment and payment
        // TODO: add applied Tax rule

        return $merchantOrder;
    }

    private function assembleItems(MerchantOrder $order, array $itemValues)
    {
        $items = [];

        foreach ($itemValues as $id => $itemValues) {
            $items[$id] = $this->resolve(MerchantItem::class);
            foreach ($itemValues as $key => $value) {
                $items[$id]->{$key} = $value;
            }
        }

        $order->items = $items;
    }

    private function assembleAppliedDiscounts(MerchantOrder $order, array $appliedDiscounts)
    {
        $discounts = [];

        foreach ($appliedDiscounts as $id => $discountValues) {
            $discounts[$id] = $this->resolve(DiscountPresenter::class);
            foreach ($discountValues as $key => $value) {
                $discounts[$id]->{$key} = $value;
            }
        }

        $order->discounts = $discounts;
    }
}
