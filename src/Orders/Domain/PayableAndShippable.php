<?php

namespace Thinktomorrow\Trader\Orders\Domain;

use Money\Money;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethodId;
use Thinktomorrow\Trader\Shipment\Domain\ShippingRuleId;

trait PayableAndShippable
{
    private $shipmentTotal;
    private $shipmentMethodId;
    private $shipmentRuleId;

    private $paymentTotal;
    private $paymentMethodId;
    private $paymentRuleId;

    public function shipmentTotal(): Money
    {
        return $this->shipmentTotal;
    }

    public function setShipmentTotal(Money $shipmentTotal)
    {
        $this->shipmentTotal = $shipmentTotal;

        return $this;
    }

    public function shipmentMethodId()
    {
        return $this->shipmentMethodId;
    }

    public function shipmentRuleId()
    {
        return $this->shipmentRuleId;
    }

    public function setShipment(ShippingMethodId $shipmentMethodId, ShippingRuleId $shipmentRuleId)
    {
        $this->shipmentMethodId = $shipmentMethodId;
        $this->shipmentRuleId = $shipmentRuleId;
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
