<?php

namespace Purchase\Discounts\Domain\Conditions;

use Optiphar\Discounts\Condition;
use Optiphar\Discounts\Discountable;
use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Optiphar\Promos\Common\Domain\Rules\Code;
use Optiphar\Promos\Common\Domain\Rules\Rule;

class Coupon implements Condition
{
    /** @var string */
    private $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function check(Cart $cart, Discountable $eligibleForDiscount): bool
    {
        return strtolower($cart->enteredCoupon()) === strtolower($this->code);
    }

    public static function fromRule(Rule $rule, array $data = []): Condition
    {
        return static::fromCouponCodeRule($rule);
    }

    private static function fromCouponCodeRule(Code $rule)
    {
        $code = $rule->getPersistableValues()['code'];

        return new static($code);
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
        ];
    }
}
