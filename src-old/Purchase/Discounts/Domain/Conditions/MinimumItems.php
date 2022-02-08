<?php

namespace Purchase\Discounts\Domain\Conditions;

use Optiphar\Discounts\Condition;
use Optiphar\Discounts\Discountable;
use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Optiphar\Promos\Common\Domain\Rules\Rule;

class MinimumItems implements Condition
{
    /** @var int */
    private $minimumQuantity;

    /** @var array */
    private $otherConditions;

    /** @var int */
    private $whitelistedQuantity;

    public function __construct(int $minimumQuantity)
    {
        $this->minimumQuantity = $minimumQuantity;
        $this->otherConditions = [];
    }

    public function setOtherConditions(array $conditions)
    {
        $this->otherConditions = $conditions;
    }

    public function check(Cart $cart, Discountable $eligibleForDiscount): bool
    {
        if($this->minimumQuantity > $cart->quantity()) return false;

        if( ! $this->whitelistedQuantity) {
            $this->whitelistedQuantity = $cart->discountableCartItemsQuantity($this->otherConditions);
        }

        return ($this->minimumQuantity <= $this->whitelistedQuantity);
    }

    public static function fromRule(Rule $rule, array $data = []): Condition
    {
        return static::fromMinimumItemsRule($rule);
    }

    private static function fromMinimumItemsRule(\Optiphar\Promos\Common\Domain\Rules\MinimumItems $rule)
    {
        return new static($rule->getPersistableValues()['quantity']);
    }

    public function toArray(): array
    {
        return [
            'minimum_quantity' => $this->minimumQuantity,
            'other_conditions' => $this->otherConditions,
        ];
    }
}
