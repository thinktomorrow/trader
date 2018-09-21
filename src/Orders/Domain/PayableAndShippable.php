<?php

namespace Thinktomorrow\Trader\Orders\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Config;
use Thinktomorrow\Trader\Countries\CountryId;
use Thinktomorrow\Trader\Payment\Domain\PaymentMethodId;
use Thinktomorrow\Trader\Payment\Domain\PaymentRuleId;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethodId;
use Thinktomorrow\Trader\Shipment\Domain\ShippingRuleId;

trait PayableAndShippable
{
    private $business = false; // bool

    private $shippingMethodId;
    private $shippingRuleId;
    private $shippingAddressId;
    private $shippingAddress;

    private $paymentMethodId;
    private $paymentRuleId;
    private $billingAddressId;
    private $billingAddress;

    private $fallbackCountryId;

    /**
     * Is this order a business order?
     * This could imply different invoice / tax rules.
     *
     * @return bool
     */
    public function isBusiness(): bool
    {
        return (bool) $this->business;
    }

    public function setBusiness($business = true)
    {
        $this->business = (bool) $business;

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

    public function shippingCountryId(): CountryId
    {
        // note: This assumes that country identifier has country_key as naming
        if (!$country_key = $this->shippingAddress('country_key')) {
            return $this->fallbackCountryId();
        }

        return CountryId::fromIsoString($country_key);
    }

    public function billingCountryId(): CountryId
    {
        // note: This assumes that country identifier has country_key as naming
        if (!$country_key = $this->billingAddress('country_key')) {
            return $this->fallbackCountryId();
        }

        return CountryId::fromIsoString($country_key);
    }

    public function fallbackCountryId(): CountryId
    {
        return $this->fallbackCountryId ?? CountryId::fromIsoString((new Config())->get('country_id', 'BE'));
    }

    public function setFallbackCountryId(CountryId $fallbackCountryId)
    {
        $this->fallbackCountryId = $fallbackCountryId;

        return $this;
    }

    public function shippingAddress($key = null)
    {
        if ($key) {
            return isset($this->shippingAddress[$key]) ? $this->shippingAddress[$key] : null;
        }

        return $this->shippingAddress;
    }

    public function billingAddress($key = null)
    {
        if ($key) {
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

    public function shippingAddressId(): int
    {
        return $this->shippingAddressId ?? 0;
    }

    public function setShippingAddressId($shippingAddressId)
    {
        $this->shippingAddressId = $shippingAddressId;
    }

    public function billingAddressId(): int
    {
        return $this->billingAddressId ?? 0;
    }

    public function setBillingAddressId($billingAddressId)
    {
        $this->billingAddressId = $billingAddressId;
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
