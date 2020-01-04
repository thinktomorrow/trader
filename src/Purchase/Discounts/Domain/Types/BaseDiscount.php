<?php

namespace Optiphar\Discounts\Types;

use Assert\Assertion;
use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Optiphar\Discounts\Condition;
use Optiphar\Discounts\Conditions\ConditionKey;
use Optiphar\Discounts\DiscountId;
use Optiphar\Discounts\EligibleForDiscount;

abstract class BaseDiscount
{
    /**
     * @var DiscountId
     */
    protected $id;

    /**
     * @var Condition[]
     */
    protected $conditions;

    /**
     * @var array
     */
    protected $data;

    public function __construct(DiscountId $id, array $conditions, array $data = [])
    {
        $this->id = $id;
        $this->conditions = $conditions;

        // Custom data, e.g. discount text for display on site or shopping cart
        $this->data = $data;

        $this->assertDataIntegrity();
    }

    protected function assertDataIntegrity()
    {
        Assertion::allIsInstanceOf($this->conditions, Condition::class);
    }

    public function id(): DiscountId
    {
        return $this->id;
    }

    public function getType(): string
    {
        return TypeKey::fromDiscount($this)->get();
    }

    /**
     * Check if the discount is somehow applicable to the cart or a part of it.
     *
     * @param Cart $cart
     * @return bool
     */
    public function overallApplicable(Cart $cart): bool
    {
        return $this->applicable($cart, $cart);
    }

    public function applicable(Cart $cart, EligibleForDiscount $eligibleForDiscount): bool
    {
        if ($this->greaterThanPrice($cart, $eligibleForDiscount) || $this->discountAmountBelowZero($cart, $eligibleForDiscount)) {
            return false;
        }

        foreach ($this->conditions as $condition) {
            if (false == $condition->check($cart, $eligibleForDiscount)) {
                return false;
            }
        }

        return true;
    }

    public function usesCondition(string $condition_key): bool
    {
        foreach ($this->conditions as $condition) {
            if (ConditionKey::fromString($condition_key)->equalsClass($condition)) {
                return true;
            }
        }

        return false;
    }

    public function isCombinable(): bool
    {
        return $this->data('is_combinable', false);
    }

    public function group(): ?string
    {
        return $this->data('group', null);
    }

    public function data($key = null, $default = null)
    {
        if (!$key) {
            return $this->data;
        }

        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return $default;
    }

    public function conditions(): array
    {
        return $this->conditions;
    }

    public function condition(ConditionKey $conditionKey): ?Condition
    {
        foreach($this->conditions as $condition)
        {
            if ($conditionKey->equalsClass($condition)) {
                return $condition;
            }
        }

        return null;
    }

    protected function getConditionKey($string): ConditionKey
    {
        return ConditionKey::fromString($string);
    }

    private function greaterThanPrice(Cart $cart, EligibleForDiscount $eligibleForDiscount): bool
    {
        // Protect against negative overflow where total would dive under zero - Discount total cannot be higher than discount base.
        $discountTotal = $eligibleForDiscount->discountTotalAsMoney()->add($this->discountAmount($cart, $eligibleForDiscount));

        return $discountTotal->greaterThan($eligibleForDiscount->discountBasePriceAsMoney($this->conditions));
    }

    private function discountAmountBelowZero(Cart $cart, EligibleForDiscount $eligibleForDiscount): bool
    {
        $discountTotal = $eligibleForDiscount->discountTotalAsMoney()->add($this->discountAmount($cart, $eligibleForDiscount));

        return $discountTotal->isNegative();
    }
}
