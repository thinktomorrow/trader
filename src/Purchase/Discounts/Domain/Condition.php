<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Discounts\Domain;

use Optiphar\Cart\Cart;
use Optiphar\Promos\Common\Domain\Rules\Rule;

interface Condition
{
    public static function fromRule(Rule $rule, array $data = []): Condition;

    public function check(Cart $cart, Discountable $eligibleForDiscount): bool;

    public function toArray(): array;
}
