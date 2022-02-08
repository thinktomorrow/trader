<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

class CategoryWhitelist implements Condition
{
    /** @var array */
    private $categoryIds;

    public function __construct(array $categoryIds)
    {
        $this->categoryIds = $categoryIds;
    }

    public function check(Cart $cart, Discountable $eligibleForDiscount): bool
    {
        if (empty($this->categoryIds)) {
            return true;
        }

        // When we check on cart level, we check if at least one of the items matches the check
        if ($eligibleForDiscount instanceof Cart) {
            return $this->checkAtLeastOneItemMatches($eligibleForDiscount);
        }

        return $this->checkItem($cart, $eligibleForDiscount);
    }

    private function checkItem(Cart $cart, CartItem $item): bool
    {
        return count(array_intersect($item->categoryIds(), $this->categoryIds)) > 0;
    }

    private function checkAtLeastOneItemMatches(Cart $cart): bool
    {
        foreach ($cart->items() as $item) {
            if ($this->checkItem($cart, $item)) {
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
        $categoryIds = $rule->getPersistableValues()['category_ids'];

        return new static($categoryIds);
    }

    public function toArray(): array
    {
        return [
            'category_ids' => $this->categoryIds,
        ];
    }
}
