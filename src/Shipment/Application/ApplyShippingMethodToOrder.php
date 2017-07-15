<?php

namespace Thinktomorrow\Trader\Shipment\Application;

use Thinktomorrow\Trader\Order\Domain\OrderId;
use Thinktomorrow\Trader\Order\Domain\OrderRepository;
use Thinktomorrow\Trader\Shipment\Domain\Exceptions\CannotApplyShippingRuleException;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethodId;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethodRepository;

class ApplyShippingMethodToOrder
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var ShippingMethodRepository
     */
    private $shippingMethodRepository;

    public function __construct(OrderRepository $orderRepository, ShippingMethodRepository $shippingMethodRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->shippingMethodRepository = $shippingMethodRepository;
    }

    public function handle(OrderId $orderId, ShippingMethodId $shippingMethodId)
    {
        try{

            // get instances via repo
            $order = $this->orderRepository->find($orderId);
            $shippingMethod = $this->shippingMethodRepository->find($shippingMethodId);

            // Find the first matching shipping rule and apply it on the order
            $shippingMethod->apply($order);
        }
        catch(CannotApplyShippingRuleException $e)
        {
            //
        }
    }
}