<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicablePromo\Discounts;

use Money\Money;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\Discount;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discountable;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

class PercentageOffDiscount extends BaseDiscount implements Discount
{
    private Percentage $percentage;

    public static function getMapKey(): string
    {
        return 'percentage_off';
    }

    public function isApplicable(Order $order, Discountable $discountable): bool
    {
        // This is a global discount: it only applies to the order total
        if (! $discountable instanceof Order) {
            return false;
        }

        return parent::isApplicable($order, $discountable);
    }

    public function getDiscountTotal(Order $order, Discountable $discountable): Money
    {
        return Cash::from(
            $order->getSubTotal()->getIncludingVat()
        )->percentage($this->percentage);
    }

    public static function fromMappedData(array $state, array $aggregateState, array $conditions): static
    {
        $values = json_decode($state['values'], true);

        $discount = new static();
        $discount->percentage = Percentage::fromString($values['percentage']);
        $discount->conditions = $conditions;

        return $discount;
    }
}
