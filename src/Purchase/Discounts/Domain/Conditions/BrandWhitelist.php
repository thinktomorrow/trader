<?php

namespace Optiphar\Discounts\Conditions;

use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Thinktomorrow\Trader\Purchase\Cart\CartItem;
use Optiphar\Discounts\Condition;
use Optiphar\Discounts\EligibleForDiscount;
use Optiphar\Promos\Common\Domain\Rules\Rule;

class BrandWhitelist implements Condition
{
    /** @var array */
    private $brandIds;

    public function __construct(array $brandIds)
    {
        $this->brandIds = $brandIds;
    }

    public function check(Cart $cart, EligibleForDiscount $eligibleForDiscount): bool
    {
        if (empty($this->brandIds)) {
            return true;
        }

        // When we check on cart level, we check if at least one of the items matches the check
        if($eligibleForDiscount instanceof Cart){
            return $this->checkAtLeastOneItemMatches($eligibleForDiscount);
        }

        return $this->checkItem($cart, $eligibleForDiscount);
    }

    private function checkItem(Cart $cart, CartItem $item): bool
    {
        return in_array($item->brandId(), $this->brandIds);
    }

    private function checkAtLeastOneItemMatches(Cart $cart): bool
    {
        foreach($cart->items() as $item){
            if($this->checkItem($cart, $item)){
                return true;
            }
        }

        return false;
    }

    public static function fromRule(Rule $rule, array $data = []): Condition
    {
        return static::fromProductWhitelistRule($rule);
    }

    private static function fromProductWhitelistRule(\Optiphar\Promos\Common\Domain\Rules\ProductWhitelist $rule)
    {
        $brandIds = $rule->getPersistableValues()['brand_ids'];

        return new static($brandIds);
    }

    public function toArray(): array
    {
        return [
            'brand_ids' => $this->brandIds,
        ];
    }
}
