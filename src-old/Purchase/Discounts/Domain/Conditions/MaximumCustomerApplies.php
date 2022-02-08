<?php

namespace Purchase\Discounts\Domain\Conditions;

use Optiphar\Cart\Cart;
use Optiphar\Discounts\Condition;
use Optiphar\Discounts\Discountable;
use Optiphar\Promos\Common\Domain\Rules\Rule;
use Optiphar\Promos\Common\Domain\Rules\UniqueRedeemer;

class MaximumCustomerApplies implements Condition
{
    /** @var int */
    private $maximum_customer_applies;

    /** @var int */
    private $current_customer_applies;

    public function __construct(int $maximum_customer_applies, int $current_customer_applies)
    {
        $this->maximum_customer_applies = $maximum_customer_applies;
        $this->current_customer_applies = $current_customer_applies;
    }

    public function check(Cart $cart, Discountable $eligibleForDiscount): bool
    {
        return $this->current_customer_applies < $this->maximum_customer_applies;
    }

    public static function fromRule(Rule $rule, array $data = []): Condition
    {
        return static::fromUniqueRedeemerRule($rule, $data);
    }

    private static function fromUniqueRedeemerRule(UniqueRedeemer $rule, array $data)
    {
        if(!isset($data['current_customer_applies'])){
            throw new \InvalidArgumentException('Condition ' . static::class . ' expects a data value with key [current_customer_applies] to be passed.');
        }

        return new static(1, $data['current_customer_applies']);
    }

    public function toArray(): array
    {
        return [
            'maximum_customer_applies' => $this->maximum_customer_applies,
            'current_customer_applies' => $this->current_customer_applies,
        ];
    }
}
