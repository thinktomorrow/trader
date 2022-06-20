<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicablePromo\Conditions;

use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discountable;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\Condition;

final class MinimumLinesQuantity implements Condition
{
    private int $minimum_quantity;

    public static function getMapKey(): string
    {
        return 'minimum_lines_quantity';
    }

    public function check(Order $order, Discountable $discountable): bool
    {
        if(!$discountable instanceof Order) return false;

        return $discountable->getQuantity()->asInt() >= $this->minimum_quantity;
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $values = json_decode($state['values'], true);

        $condition = new static();
        $condition->minimum_quantity = $values['minimum_quantity'];

        return $condition;
    }
}
