<?php

namespace Thinktomorrow\Trader\Shipment\Application;

use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Shipment\Domain\Exceptions\CannotApplyShippingRuleException;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethod;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethodId;
use Thinktomorrow\Trader\Shipment\Domain\ShippingRule;

class ApplyShippingRuleToOrder
{
    public function handle(Order $order)
    {
        try{

            // TODO find cost of implemented ShippingRule based on selected shipping method

            // Apply the first matching shipping rule of the selected shipping method
            $shippingMethod = new ShippingMethod(ShippingMethodId::fromInteger(1));

            $shippingMethod->apply($order);
        }
        catch(CannotApplyShippingRuleException $e)
        {
            //
        }
    }
}