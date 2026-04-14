<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\OrderPromo\Discounts;

use Money\Money;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderDiscount;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableItem;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\FixedAmountDiscount;

class FixedAmountOrderDiscount extends BaseOrderDiscount implements OrderDiscount
{
    private Money $amount;

    public static function getMapKey(): string
    {
        return FixedAmountDiscount::getMapKey();
    }

    public function isApplicable(Order $order, DiscountableItem $discountable): bool
    {
        // This is a global discount: it only applies to the order total
        if (! $discountable instanceof Order) {
            return false;
        }

        return parent::isApplicable($order, $discountable);
    }

    public function getDiscountPrice(Order $order, DiscountableItem $discountable): DiscountPrice
    {
        $discountMoney = $this->amount;

        if ($discountMoney->greaterThanOrEqual($order->getTotalExcl())) {
            $discountMoney = $order->getTotalExcl();
        }

        return DefaultDiscountPrice::fromExcludingVat($discountMoney);
    }

    public static function fromMappedData(array $state, array $aggregateState, array $conditions): static
    {
        $discount = parent::fromMappedData($state, $aggregateState, $conditions);

        $data = json_decode($state['data'], true);
        $discount->amount = Cash::make($data['amount']);

        return $discount;
    }
}
