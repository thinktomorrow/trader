<?php

namespace Thinktomorrow\Trader\Order\Application;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Order\Domain\OrderId;
use Thinktomorrow\Trader\Order\Domain\OrderRepository;
use Thinktomorrow\Trader\Order\Ports\Web\Merchant\Item;
use Thinktomorrow\Trader\Order\Ports\Web\Merchant\Order as MerchantOrder;
use Thinktomorrow\Trader\Order\Ports\Web\Merchant\Order;

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
        $data = $this->orderRepository->getValuesForMerchantOrder(OrderId::fromInteger($orderId));

        $order = new MerchantOrder();

        foreach(['total','subtotal','discount_total', 'payment_total','shipment_total','tax','tax_rates','reference','confirmed_at','state'] as $attribute)
        {
            $order->{$attribute} = $data[$attribute];
        }

        $this->assembleItems($order,$data['items']);

        // TODO items should be coming from db and transposed to Web\Merchant\Item

        // TODO: add applied discounts for both order and items
        // TODO: add applied shipment and payment
        // TODO: add applied Tax rule

        return $order;
    }

    private function assembleItems(Order $order, array $itemValues)
    {
        $items = [];

        foreach($itemValues as $id => $itemValue)
        {
            $items[$id] = new Item($itemValue);
        }

        $order->items = $items;
    }
}