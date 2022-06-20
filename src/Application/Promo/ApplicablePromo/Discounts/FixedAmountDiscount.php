<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicablePromo\Discounts;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discountable;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\Discount;

class FixedAmountDiscount extends BaseDiscount implements Discount
{
    private Money $amount;

    public static function getMapKey(): string
    {
        return 'fixed_amount';
    }

    public function isApplicable(Order $order, Discountable $discountable): bool
    {
        // This is a global discount: it only applies to the order total
        if(! $discountable instanceof Order) return false;

        return parent::isApplicable($order, $discountable);
    }

    public function getDiscountTotal(Order $order, Discountable $discountable): Money
    {
        return $this->amount;
    }

    public static function fromMappedData(array $state, array $aggregateState, array $conditions): static
    {
        $values = json_decode($state['values'], true);

        $discount = new static();
        $discount->amount = Cash::make($values['amount']);
        $discount->conditions = $conditions;

        return $discount;
    }
}
