<?php

namespace Thinktomorrow\Trader\Tax\Domain\Rules;

use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Tax\Domain\Taxable;
use Thinktomorrow\Trader\Tax\Domain\TaxRate;

class ForeignBusinessRule implements TaxRule
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
        if (!$this->order || !$this->order->business()) {
            return false;
        }

        if (!$this->order->billingCountryId() || !$this->taxRate->merchantCountryId()) {
            return false;
        }

        // Valid business outside the sender country receives tax exemption
        // TODO: rule for consumers outside europe or in Norway and Swissland also qualify for this.
        // @ref: https://ecom-support.lightspeedhq.com/hc/nl/articles/115005022268-BTW-regels-hoe-werkt-het-precies-
        return !$this->order->billingCountryId()->equals($this->taxRate->merchantCountryId());

        return false;
    }

    public function apply(Percentage $taxPercentage): Percentage
    {
        return Percentage::fromPercent(0);
    }
}
