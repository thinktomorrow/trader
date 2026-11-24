<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts;

use Money\Money;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Application\Promo\Preview\PromoPreviewProduct;
use Thinktomorrow\Trader\Application\VatRate\Allocator\VatAllocator;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\DiscountableItem;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\SalePriceSystemDiscount;

class SalePriceProductDiscount extends BaseApplicableDiscount implements ProductDiscount
{
    private TaxMode $calculationTaxMode = TaxMode::Inclusive;

    public static function getMapKey(): string
    {
        return SalePriceSystemDiscount::getMapKey();
    }

    protected static function fixedDiscountableType(): ?DiscountableType
    {
        return SalePriceSystemDiscount::fixedDiscountableType();
    }

    public function couldApply(Order $order): bool
    {
        if (! parent::couldApply($order)) {
            return false;
        }

        // Conditions may evaluate differently after other discounts are applied.
        // Only rule out a sale price when its applicability depends solely on line data.
        if ($this->getConditions() !== []) {
            return true;
        }

        foreach ($order->getLines() as $line) {
            if ($this->isApplicable($order, $line)) {
                return true;
            }
        }

        return false;
    }

    /** Only a product with a sale price is relevant to this line discount. */
    protected function isPreviewRelevant(PromoPreviewProduct|Cart|CartShipping|CartPayment $subject): bool
    {
        return $subject instanceof PromoPreviewProduct && $subject->isOnSale();
    }

    public function isApplicable(Order $order, DiscountableItem $discountable): bool
    {
        // This only applies to a line where the sale price is set
        if (! $discountable instanceof Line) {
            return false;
        }

        if (! $discountable->getData('sale_price_excl') || ! $discountable->getData('sale_price_incl')) {
            return false;
        }
        if (! $discountable->getData('unit_price_excl') || ! $discountable->getData('unit_price_incl')) {
            return false;
        }

        if ($discountable->getData('sale_price_excl') >= $discountable->getData('unit_price_excl')) {
            return false;
        }

        return parent::isApplicable($order, $discountable);
    }

    public function getDiscountPrice(Order $order, DiscountableItem $discountable): ItemDiscountPrice
    {
        if (! $discountable instanceof Line) {
            throw new \InvalidArgumentException('A sale price discount can only be calculated for an order line.');
        }

        $unitPrice = $discountable->getUnitPrice();

        if ($this->calculationTaxMode === TaxMode::Inclusive && $unitPrice->isIncludingVatAuthoritative()) {
            $salePriceIncl = Money::EUR($discountable->getData('sale_price_incl'));

            $discountMoney = $unitPrice->getIncludingVat()->subtract($salePriceIncl);

            return DefaultItemDiscountPrice::fromIncludingVat($discountMoney, $unitPrice->getVatPercentage());
        }

        $salePriceExcl = Money::EUR($discountable->getData('sale_price_excl'));
        $discountMoney = $unitPrice->getExcludingVat()->subtract($salePriceExcl);

        return DefaultItemDiscountPrice::fromExcludingVat($discountMoney, $unitPrice->getVatPercentage());
    }

    public function setCalculationTaxMode(TaxMode $taxMode): void
    {
        $this->calculationTaxMode = $taxMode;
    }

    public static function fromMappedData(array $state, array $aggregateState, array $conditions, VatAllocator $vatAllocator): static
    {
        return parent::fromMappedData($state, $aggregateState, $conditions, $vatAllocator);
    }
}
