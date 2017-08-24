<?php

namespace Thinktomorrow\Trader\Orders\Domain;

use Money\Money;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethodId;
use Thinktomorrow\Trader\Shipment\Domain\ShippingRuleId;

trait PayableAndShippable
{
    private $shippingTotal;
    private $shippingMethodId;
    private $shippingRuleId;

    private $paymentTotal;
    private $paymentMethodId;
    private $paymentRuleId;

    public function shippingTotal(): Money
    {
        return $this->shippingTotal;
    }

    public function setShippingTotal(Money $shippingTotal)
    {
        $this->shippingTotal = $shippingTotal;

        return $this;
    }

    public function shippingMethodId()
    {
        return $this->shippingMethodId;
    }

    public function shippingRuleId()
    {
        return $this->shippingRuleId;
    }

    public function setShipping(ShippingMethodId $shipmentMethodId, ShippingRuleId $shipmentRuleId)
    {
        $this->shippingMethodId = $shipmentMethodId;
        $this->shippingRuleId = $shipmentRuleId;
    }

    public function paymentTotal(): Money
    {
        return $this->paymentTotal;
    }

    public function setPaymentTotal(Money $paymentTotal)
    {
        $this->paymentTotal = $paymentTotal;

        return $this;
    }

    public function paymentMethodId()
    {
        return $this->paymentMethodId;
    }

    public function paymentRuleId()
    {
        return $this->paymentRuleId;
    }

    public function setPayment(PaymentMethodId $paymentMethodId, PaymentRuleId $paymentRuleId)
    {
        $this->paymentMethodId = $paymentMethodId;
        $this->paymentRuleId = $paymentRuleId;
    }
}
