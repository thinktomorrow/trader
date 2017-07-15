<?php

namespace Thinktomorrow\Trader\Tax\Domain;

use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Order\Domain\Order;

class OrderTaxRate
{
    /**
     * @var TaxRate
     */
    private $taxRate;
    /**
     * @var Order
     */
    private $order;

    public function __construct(TaxRate $taxRate, Order $order)
    {
        $this->taxRate = $taxRate;
        $this->order = $order;
    }

    public function get(): Percentage
    {
        // TODO: set all order specific stuff on tax to get expected rate.
        // $this->taxRate->forBillingCountry(); ...

        return $this->taxRate->get();
    }
}