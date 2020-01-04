<?php

namespace Optiphar\Discounts;

use Optiphar\Cart\Cart;
use Optiphar\Promos\Common\Domain\Rules\Rule;

interface Condition
{
    public static function fromRule(Rule $rule, array $data = []): Condition;

    public function check(Cart $cart, EligibleForDiscount $eligibleForDiscount): bool;

    public function toArray(): array;
}
