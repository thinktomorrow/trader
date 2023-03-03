<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\OrderPromo\Discounts;

use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderDiscount;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discountable;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountPriceDefaults;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PercentageOffDiscount;

class PercentageOffOrderDiscount extends BaseDiscount implements OrderDiscount
{
    private Percentage $percentage;

    public static function getMapKey(): string
    {
        return PercentageOffDiscount::getMapKey();
    }

    public function isApplicable(Order $order, Discountable $discountable): bool
    {
        // This is a global discount: it only applies to the order total
        if (! $discountable instanceof Order) {
            return false;
        }

        return parent::isApplicable($order, $discountable);
    }

    public function getDiscountTotal(Order $order, Discountable $discountable): DiscountTotal
    {
        $discountMoney = Cash::from(
            $order->getSubTotal()->getIncludingVat()
        )->percentage($this->percentage);

        return DiscountTotal::fromMoney($discountMoney, DiscountPriceDefaults::getDiscountTaxRate(), $order->getSubTotal()->includesVat());
    }

    public static function fromMappedData(array $state, array $aggregateState, array $conditions): static
    {
        $discount = parent::fromMappedData($state, $aggregateState, $conditions);

        $data = json_decode($state['data'], true);
        $discount->percentage = Percentage::fromString($data['percentage']);

        return $discount;
    }
}
