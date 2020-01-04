<?php

namespace Optiphar\Discounts\Conditions;

use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Thinktomorrow\Trader\Purchase\Cart\CartItem;
use Optiphar\Discounts\Condition;
use Optiphar\Discounts\EligibleForDiscount;
use Optiphar\Promos\Common\Domain\Rules\Rule;

class ItemWhitelist implements Condition
{
    /** @var array */
    private $productIds;

    public function __construct(array $productIds)
    {
        $this->productIds = $productIds;
    }

    public function check(Cart $cart, EligibleForDiscount $eligibleForDiscount): bool
    {
        if (empty($this->productIds)) {
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
        return in_array($item->productId(), $this->productIds);
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
        $productIds = $rule->getPersistableValues()['product_ids'];

        return new static($productIds);
    }

    public function toArray(): array
    {
        return [
            'product_ids' => $this->productIds,
        ];
    }
}
