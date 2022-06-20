<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicablePromo\Discounts;

use Money\Money;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\ApplicableCondition;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discountable;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

abstract class BaseDiscount
{
    /** @var ApplicableCondition[] */
    protected array $conditions;

    protected function isApplicable(Order $order, Discountable $discountable): bool
    {
        foreach ($this->conditions as $condition) {
            if (! $condition->check($order, $discountable)) {
                return false;
            }
        }

        return true;
    }

    public function apply(Order $order, Discountable $discountable): void
    {
        if (! $this->isApplicable($order, $discountable)) {
            return;
        }

        $discountable->addDiscount(new \Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount(
            $order->orderId,
            $this->getDiscountTotal($order, $discountable),
            [], // TODO: How to pass data such as title, description, ...
        ));
    }

    abstract public function getDiscountTotal(Order $order, Discountable $discountable): Money;
}
