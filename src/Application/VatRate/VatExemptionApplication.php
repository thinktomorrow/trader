<?php

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\TraderConfig;

class VatExemptionApplication
{
    public function __construct(private TraderConfig $config)
    {

    }

    /**
     * If the shopper is business with valid VAT and billing country is different
     * from the shop's country, the shopper is eligible for vat exemption.
     */
    public function verifyForOrder(Order $order): bool
    {
        if (! $this->config->isVatExemptionAllowed()) {
            return false;
        }

        if (! $order->getShopper() || ! $order->getShopper()->isBusiness()) {
            return false;
        }

        if (! $order->getShopper()->isVatNumberValid()) {
            return false;
        }

        // Verify that the shopper's VAT number country matches the billing address country.
        if ($order->getShopper()->getVatNumberCountry() !== $order->getBillingAddress()?->getAddress()->countryId?->get()) {
            return false;
        }

        $merchantCountryIds = [$this->config->getPrimaryVatCountry()];

        return ! in_array($order->getShopper()->getVatNumberCountry(), $merchantCountryIds);
    }
}
