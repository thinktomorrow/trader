<?php

namespace Thinktomorrow\Trader\Tax\Domain\Rules;

use Thinktomorrow\Trader\Common\Price\Percentage;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Tax\Domain\Taxable;
use Thinktomorrow\Trader\Tax\Domain\TaxRate;

class BillingCountryRule implements TaxRule
{
    private $taxRate;
    private $taxable;
    private $order;

    public function context(TaxRate $taxRate, Taxable $taxable = null, Order $order = null)
    {
        $this->taxRate = $taxRate;
        $this->taxable = $taxable;
        $this->order = $order;

        return $this;
    }

    public function applicable(): bool
    {
        if (!$this->order) {
            return false;
        }

        return (bool) ($this->getEligibleCountryRate());
    }

    public function apply(Percentage $taxPercentage): Percentage
    {
        if ($countryRate = $this->getEligibleCountryRate()) {
            return $countryRate->get();
        }

        return $taxPercentage;
    }

    private function getEligibleCountryRate()
    {
        foreach ($this->taxRate->billingCountryRates() as $countryRate) {
            if ($countryRate->matchesCountry($this->order->billingCountryId())) {
                return $countryRate;
            }
        }
    }
}
