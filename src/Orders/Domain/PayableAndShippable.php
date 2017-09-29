<?php

namespace Thinktomorrow\Trader\Orders\Domain;

use Money\Money;
use Thinktomorrow\Trader\Payment\Domain\PaymentMethodId;
use Thinktomorrow\Trader\Payment\Domain\PaymentRuleId;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethodId;
use Thinktomorrow\Trader\Shipment\Domain\ShippingRuleId;

trait PayableAndShippable
{
    private $shippingTotal;
    private $shippingMethodId;
    private $shippingRuleId;
    private $shippingAddressId;
    private $shippingAddress;

    private $paymentTotal;
    private $paymentMethodId;
    private $paymentRuleId;
    private $billingAddressId;
    private $billingAddress;

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

    public function shippingAddress($key = null)
    {
        if($key)
        {
            return isset($this->shippingAddress[$key]) ? $this->shippingAddress[$key] : null;
        }

        return $this->shippingAddress;
    }

    public function billingAddress($key = null)
    {
        if($key)
        {
            return isset($this->billingAddress[$key]) ? $this->billingAddress[$key] : null;
        }

        return $this->billingAddress;
    }

    public function setShippingAddress(array $address)
    {
        $this->shippingAddress = $address;

        return $this;
    }

    public function setBillingAddress(array $address)
    {
        $this->billingAddress = $address;

        return $this;
    }

    public function shippingAddressId()
    {
        return $this->shippingAddressId;
    }

    public function setShippingAddressId($shippingAddressId)
    {
        $this->shippingAddressId = $shippingAddressId;
    }

    public function billingAddressId()
    {
        return $this->billingAddressId;
    }

    public function setBillingAddressId($billingAddressId)
    {
        $this->billingAddressId = $billingAddressId;
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
