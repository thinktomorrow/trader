<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\OrderPromo\Conditions;

use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderCondition;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableItem;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumLinesQuantity;

final class MinimumLinesQuantityOrderCondition implements OrderCondition
{
    private int $minimum_quantity;

    public static function getMapKey(): string
    {
        return MinimumLinesQuantity::getMapKey();
    }

    public function check(Order $order, DiscountableItem $discountable): bool
    {
        if (! $discountable instanceof Order) {
            return false;
        }

        return $discountable->getQuantity()->asInt() >= $this->minimum_quantity;
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $data = json_decode($state['data'], true);

        $condition = new self;
        $condition->minimum_quantity = $data['minimum_quantity'];

        return $condition;
    }
}
