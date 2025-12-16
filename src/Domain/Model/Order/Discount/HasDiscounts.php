<?php

namespace Thinktomorrow\Trader\Domain\Model\Order\Discount;

use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;

trait HasDiscounts
{
    /** @var Discount[] */
    private array $discounts = [];

    public function getSumOfDiscountPrices(): DiscountPrice
    {
        $totalDiscount = DefaultDiscountPrice::zero();

        /** @var Discount $discount */
        foreach ($this->discounts as $discount) {
            $totalDiscount = $totalDiscount->add($discount->getDiscountPrice());
        }

        return $totalDiscount;
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
