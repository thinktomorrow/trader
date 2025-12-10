<?php

namespace Thinktomorrow\Trader\Domain\Model\Order\Discount;

use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemDiscount;
use Thinktomorrow\Trader\Domain\Common\Price\ItemDiscount;
use Thinktomorrow\Trader\Domain\Common\Price\ItemPrice;
use Thinktomorrow\Trader\Domain\Common\Price\Price;

trait HasDiscounts
{
    /** @var Discount[] */
    private array $discounts = [];

    /**
     * Normally the discount is calculated with the default discount tax rate, as set via the DiscountPriceDefaults::setDiscountTaxRate().
     * However, to calculate discounts on specific items of the order, such as lines or shipping, we need to alter this tax
     * rate in order to give the proper discount per line.
     *
     * @return DiscountTotal
     */
    protected function calculateItemDiscount(Price|ItemPrice $price): ItemDiscount
    {
        $vatPercentage = $price instanceof ItemPrice ? $price->getVatPercentage() : DiscountPriceDefaults::getDiscountTaxRate();

        $itemDiscount = DefaultItemDiscount::fromExcludingVat(
            Cash::zero(),
            $vatPercentage,
        );

        foreach ($this->discounts as $discount) {
            $itemDiscount = $itemDiscount->add($discount->getItemDiscount());
        }

        if ($itemDiscount->getExcludingVat()->greaterThanOrEqual($price->getExcludingVat())) {
            return DefaultItemDiscount::fromExcludingVat($price->getExcludingVat(), $vatPercentage);
        }

        return $itemDiscount;
    }

    public function addDiscount(Discount $discount): void
    {
        $this->assertDiscountCanBeAdded($discount);

        $this->discounts[] = $discount;
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

    /**
     * @param Discount $discount
     * @return void
     */
    private function assertDiscountCanBeAdded(Discount $discount): void
    {
        // TODO:: test assert order id matches
        // TODO:: test assert owner_type and owner_id matches
        // TODO: test assert discount isnt already added... (cf. addShipping)

        if (!$discount->discountableId->equals($this->getDiscountableId())) {
            throw new \InvalidArgumentException('Cannot add discount when discountable id doesn\'t match. Discountable id: ' . $this->getDiscountableId()->get() . '. Passed: ' . $discount->discountableId->get());
        }

        if ($discount->discountableType !== $this->getDiscountableType()) {
            throw new \InvalidArgumentException('Cannot add discount when discountable type doesn\'t match.  Discountable type: ' . $this->getDiscountableType()->value . '. Passed: ' . $discount->discountableType->value);
        }

        if (in_array($discount->discountId, array_map(fn(Discount $discount) => $discount->discountId, $this->discounts))) {
            throw new \InvalidArgumentException('Cannot add same discount (with same discount id: ' . $discount->discountId->get() . ') twice.');
        }

        if (in_array($discount->promoDiscountId, array_map(fn(Discount $discount) => $discount->promoDiscountId, $this->discounts))) {
            throw new \InvalidArgumentException('Cannot add same discount (with same promo discount id: ' . $discount->promoDiscountId->get() . ') twice.');
        }
    }
}
