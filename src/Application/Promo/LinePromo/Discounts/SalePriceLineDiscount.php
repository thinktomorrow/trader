<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\LinePromo\Discounts;

use Money\Money;
use Thinktomorrow\Trader\Application\Promo\LinePromo\LineDiscount;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\Discounts\BaseOrderDiscount;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\ItemPrice;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableItem;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\SalePriceSystemDiscount;

class SalePriceLineDiscount extends BaseOrderDiscount implements LineDiscount
{
    /**
     * Whether to calculate the discount based on prices excluding VAT.
     * For B2B scenarios where prices are calculated excluding VAT.
     * In B2C scenarios, prices are typically calculated including VAT.
     */
    private bool $calculateExcludingVat = false;

    public static function getMapKey(): string
    {
        return SalePriceSystemDiscount::getMapKey();
    }

    public function isApplicable(Order $order, DiscountableItem $discountable): bool
    {
        // This only applies to a line where the sale price is set
        if (!$discountable instanceof Line) {
            return false;
        }

        if (!$discountable->getData('sale_price_excl') || !$discountable->getData('sale_price_incl')) {
            return false;
        }
        if (!$discountable->getData('unit_price_excl') || !$discountable->getData('unit_price_incl')) {
            return false;
        }

        if ($discountable->getData('sale_price_excl') >= $discountable->getData('unit_price_excl')) {
            return false;
        }

        return parent::isApplicable($order, $discountable);
    }

    public function getDiscountPrice(Order $order, DiscountableItem $discountable): ItemDiscountPrice
    {
        /** @var ItemPrice $unitPrice */
        $unitPrice = $discountable->getUnitPrice();

        if (!$this->calculateExcludingVat && $unitPrice->isIncludingVatAuthoritative()) {

            $salePriceIncl = Money::EUR($discountable->getData('sale_price_incl'));

            $discountMoney = $unitPrice->getIncludingVat()->subtract($salePriceIncl);
            return DefaultItemDiscountPrice::fromIncludingVat($discountMoney, $unitPrice->getVatPercentage());
        }

        $salePriceExcl = Money::EUR($discountable->getData('sale_price_excl'));
        $discountMoney = $unitPrice->getExcludingVat()->subtract($salePriceExcl);

        return DefaultItemDiscountPrice::fromExcludingVat($discountMoney, $unitPrice->getVatPercentage());
    }

    public function setCalculateExcludingVat(bool $calculateExcludingVat): void
    {
        $this->calculateExcludingVat = $calculateExcludingVat;
    }

    public static function fromMappedData(array $state, array $aggregateState, array $conditions): static
    {
        return parent::fromMappedData($state, $aggregateState, $conditions);
    }
}
