<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Money\Money;
use Optiphar\Discounts\Condition;
use Optiphar\Discounts\Discountable;
use Optiphar\Promos\Common\Domain\Rules\Rule;
use Thinktomorrow\Trader\Purchase\Cart\Cart;

class MinimumAmount implements Condition
{
    /** @var Money */
    private $minimumAmount;

    public function __construct(Money $minimumAmount)
    {
        $this->minimumAmount = $minimumAmount;
    }

    public function check(Cart $cart, Discountable $eligibleForDiscount): bool
    {
        // We need the subtotal but without the added item discounts.
        $subTotalWithoutItemDiscounts = $cart->items()->reduce(function ($carry, $item) {
            return $carry->add($item->salePrice()->multiply($item->quantity()));
        }, Money::EUR(0));

        // This condition is only applied to the entire cart, not to a specific
        // item or part of it. Check subtotal (without shipment / payment costs)
        return $subTotalWithoutItemDiscounts->greaterThanOrEqual($this->minimumAmount);
    }

    public static function fromRule(Rule $rule, array $data = []): Condition
    {
        return static::fromMinimumAmountRule($rule);
    }

    private static function fromMinimumAmountRule(\Optiphar\Promos\Common\Domain\Rules\MinimumAmount $rule)
    {
        return new static($rule->getAmount());
    }

    public function toArray(): array
    {
        return [
            'minimum_amount' => $this->minimumAmount,
        ];
    }
}
