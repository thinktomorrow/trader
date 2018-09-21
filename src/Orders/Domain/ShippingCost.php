<?php

namespace Thinktomorrow\Trader\Orders\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Price\Cash;
use Thinktomorrow\Trader\Common\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;

class ShippingCost implements EligibleForDiscount
{
    /** @var Money */
    private $subtotal;

    /** @var Money */
    private $discountTotal;

    /** @var array */
    private $discounts;

    /** @var Percentage */
    private $discountPercentage;

    public function __construct()
    {
        $this->subtotal = Cash::make(0);
        $this->discountTotal = Cash::make(0);
        $this->discounts = [];
    }

    public function discountBasePrice(): Money
    {
        return $this->subtotal();
    }

    public function discountTotal(): Money
    {
        return $this->discountTotal;
    }

    public function addToDiscountTotal(Money $addition)
    {
        $this->discountTotal = $this->discountTotal->add($addition);
    }

    public function discounts(): array
    {
        return $this->discounts;
    }

    public function addDiscount(AppliedDiscount $appliedDiscount)
    {
        $this->discounts[] = $appliedDiscount;
    }

    public function setSubtotal(Money $subtotal)
    {
        $this->subtotal = $subtotal;
    }

    public function subtotal(): Money
    {
        return $this->subtotal;
    }

    public function total(): Money
    {
        return $this->subtotal()
            ->subtract($this->discountTotal());
    }

    public function discountPercentage(): Percentage
    {
        return $this->discountPercentage;
    }

    public function setDiscountPercentage(Percentage $percentage)
    {
        $this->discountPercentage = $percentage;
    }

    public function removeDiscounts()
    {
        $this->discountTotal = Cash::make(0);
        $this->discountPercentage = Percentage::fromPercent(0);
        $this->discounts = [];
    }
}
