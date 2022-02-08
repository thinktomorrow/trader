<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Discounts\Domain\Condition;
use Thinktomorrow\Trader\Discounts\Domain\Discountable;
use Thinktomorrow\Trader\Order\Domain\Order;

class MinimumItems implements Condition
{
    private int $minimumQuantity;
    private array $otherConditions;

    public function __construct(int $minimumQuantity, array $otherConditions)
    {
        $this->minimumQuantity = $minimumQuantity;
        $this->otherConditions = $otherConditions;
    }

    public function check(Order $order, Discountable $discountable): bool
    {
        if ($this->minimumQuantity > $order->getQuantity()) {
            return false;
        }

        return ($this->minimumQuantity <= $order->getDiscountableQuantity($this->otherConditions));
    }

//    public static function fromRule(Rule $rule, array $data = []): Condition
//    {
//        return static::fromMinimumItemsRule($rule);
//    }
//
//    private static function fromMinimumItemsRule(\Optiphar\Promos\Common\Domain\Rules\MinimumItems $rule)
//    {
//        return new static($rule->getPersistableValues()['quantity']);
//    }

    public function toArray(): array
    {
        return [
            'minimum_quantity' => $this->minimumQuantity,
            'other_conditions' => $this->otherConditions,
        ];
    }
}
