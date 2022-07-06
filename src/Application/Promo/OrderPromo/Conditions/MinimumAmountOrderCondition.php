<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\OrderPromo\Conditions;

use Money\Money;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderCondition;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discountable;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumAmount;

class MinimumAmountOrderCondition implements OrderCondition
{
    private Money $amount;

    public static function getMapKey(): string
    {
        return MinimumAmount::getMapKey();
    }

    public function check(Order $order, Discountable $discountable): bool
    {
        if (! $discountable instanceof Order) {
            return false;
        }

        return $discountable->getSubTotal()->getMoney()->greaterThanOrEqual($this->amount);
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $data = json_decode($state['data'], true);

        $condition = new static();
        $condition->amount = Cash::make($data['amount']);

        return $condition;
    }
}
