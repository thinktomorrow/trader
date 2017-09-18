<?php

namespace Thinktomorrow\Trader\Order\Application;

use Thinktomorrow\Trader\Discounts\Ports\Web\Discount;
use Thinktomorrow\Trader\Order\Domain\OrderId;
use Thinktomorrow\Trader\Order\Domain\OrderRepository;
use Thinktomorrow\Trader\Order\Ports\Read\MerchantItem;
use Thinktomorrow\Trader\Order\Ports\Read\MerchantOrder;

// TODO: the assembler violates the dependency flow since it depends on concrete ports objects
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

    public function forShop($orderId)
    {
        // TODO (see forMerchant as base) als use a Web/BaseOrder for common stuff
    }

    /**
     * This data (raw) will be presented as a simple read-only DTO
     * @param $orderId
     * @return MerchantOrder
     */
    public function forMerchant($orderId)
    {
        $data = $this->orderRepository->getValues(OrderId::fromInteger($orderId));

        $order = new MerchantOrder();

        foreach(['total','subtotal','discount_total', 'payment_total','shipment_total','tax','tax_rates','reference','confirmed_at','state'] as $attribute)
        {
            $order->{$attribute} = $data[$attribute];
        }

        $this->assembleItems($order,$orderId);
        $this->assembleAppliedDiscounts($order, $orderId);


        // TODO: add applied shipment and payment
        // TODO: add applied Tax rule

        return $order;
    }

    private function assembleItems(MerchantOrder $order, $orderId)
    {
        $itemCollection = $this->orderRepository->getItemValues(OrderId::fromInteger($orderId));
        $items = [];

        foreach($itemCollection as $id => $itemValues)
        {
            $items[$id] = new MerchantItem($itemValues);
        }

        $order->items = $items;
    }

    private function assembleAppliedDiscounts($order, $orderId)
    {
        $discountCollection = $this->orderRepository->getAppliedDiscounts(OrderId::fromInteger($orderId));
        $discounts = [];

        foreach($discountCollection as $id => $discountValues)
        {
            $discounts[$id] = new Discount($discountValues);
        }

        $order->discounts = $discounts;
    }
}