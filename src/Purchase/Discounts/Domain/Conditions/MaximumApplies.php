<?php

namespace Optiphar\Discounts\Conditions;

use Optiphar\Cart\Cart;
use Optiphar\Discounts\Condition;
use Optiphar\Discounts\EligibleForDiscount;
use Optiphar\Promos\Common\Domain\Rules\Rule;
use Optiphar\Promos\Common\Domain\Rules\MaximumRedemption;

class MaximumApplies implements Condition
{
    /** @var int */
    private $maximum_applies;

    /** @var int */
    private $current_applies;

    public function __construct(int $maximum_applies, int $current_applies)
    {
        $this->maximum_applies = $maximum_applies;
        $this->current_applies = $current_applies;
    }

    public function check(Cart $cart, EligibleForDiscount $eligibleForDiscount): bool
    {
        return $this->current_applies < $this->maximum_applies;
    }

    public static function fromRule(Rule $rule, array $data = []): Condition
    {
        return static::fromMaximumRedemptionRule($rule, $data);
    }

    private static function fromMaximumRedemptionRule(MaximumRedemption $rule, array $data)
    {
        if(!isset($data['current_applies'])){
            throw new \InvalidArgumentException('Condition ' . static::class . ' expects a data value with key [current_applies] to be passed.');
        }

        $max = $rule->getPersistableValues()['max'];
        $current_applies = $data['current_applies'];

        return new static($max, $current_applies);
    }

    public function toArray(): array
    {
        return [
            'maximum_applies' => $this->maximum_applies,
            'current_applies' => $this->current_applies,
        ];
    }
}
