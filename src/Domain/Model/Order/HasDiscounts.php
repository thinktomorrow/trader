<?php

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Common\Cash\PriceTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;

trait HasDiscounts
{
    private array $discounts = [];

    /**
     * Normally the discount is calculated with the default discount tax rate, as set via the DiscountTotal::setDiscountTaxRate().
     * However, to calculate discounts on specific items of the order, such as lines or shipping, we need to alter this tax
     * rate in order to give the proper discount per line.
     *
     * @return DiscountTotal
     */
    protected function calculateDiscountTotal(Price|PriceTotal $basePrice): DiscountTotal
    {
        $discountTaxRate = DiscountTotal::getDiscountTaxRate();

        if(count($this->discounts) > 0) {
          $discountTaxRate = $this->discounts[0]->getTotal()->getTaxRate();
        } else if($basePrice instanceof Price) {
            $discountTaxRate = $basePrice->getTaxRate();
        }

        $zeroDiscountTotal = DiscountTotal::fromMoney(
            Cash::zero(),
            $discountTaxRate,
            true
        );

        if (count($this->discounts) < 1) {
            return $zeroDiscountTotal;
        }

        $discountTotal = array_reduce($this->discounts, function (?DiscountTotal $carry, Discount $discount) {
            return $carry === null
                ? $discount->getTotal()
                : $carry->add($discount->getTotal());
        }, $zeroDiscountTotal);

        if($discountTotal->getIncludingVat()->greaterThanOrEqual($basePrice->getIncludingVat())) {
            return DiscountTotal::fromDefault($basePrice->getIncludingVat());
        }

        return $discountTotal;
    }

    public function addDiscount(Discount $discount): void
    {
        // TODO:: assert order id matches
        // TODO: assert discount isnt already added... (cf. addShipping)

        if (! in_array($discount, $this->discounts)) {
            $this->discounts[] = $discount;
        }
    }

    public function deleteDiscount(DiscountId $discountId): void
    {
        /** @var Discount $existingDiscount */
        foreach ($this->discounts as $indexToBeDeleted => $existingDiscount) {
            if ($existingDiscount->discountId->equals($discountId)) {
                unset($this->discounts[$indexToBeDeleted]);
            }
        }
    }

    public function getDiscounts(): array
    {
        return $this->discounts;
    }

    public function deleteDiscounts(): void
    {
        $this->discounts = [];
    }
}
