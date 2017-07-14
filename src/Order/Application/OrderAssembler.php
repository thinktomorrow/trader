<?php

namespace Thinktomorrow\Trader\Order\Application;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Order\Domain\OrderId;
use Thinktomorrow\Trader\Order\Domain\OrderRepository;
use Thinktomorrow\Trader\Order\Ports\Web\Merchant\Order as MerchantOrder;

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

    /**
     * This data (raw) will then be transposed to a simple read-only DTO
     * @param $orderId
     * @return MerchantOrder
     */
    public function forMerchant($orderId)
    {
        $data = $this->orderRepository->getValuesForMerchantOrder(OrderId::fromInteger($orderId));

        $order = new MerchantOrder();

        foreach(['items','total','subtotal','payment_total','shipment_total','tax','tax_rates','reference','confirmed_at','state'] as $attribute)
        {
            $order->{$attribute} = $data[$attribute];
        }

        return $order;
    }
}