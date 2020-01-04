<?php

namespace Optiphar\Discounts;

use Money\Money;
use Optiphar\Cart\Cart;
use Optiphar\Promos\Common\Domain\Promo;
use Optiphar\Discounts\Conditions\ConditionKey;

interface Discount
{
    /**
     * Create a discount from the promo object which is unused in cart. Discount is used in
     * cart logic while the latter is still used in the admin / database layers.
     *
     * @param Promo $promo
     * @param array $conditions
     * @param array $data
     * @return Discount
     */
    public static function fromPromo(Promo $promo, array $conditions, array $data): Discount;

    public function id(): DiscountId;

    public function getType(): string;

    public function overallApplicable(Cart $cart): bool;

    public function applicable(Cart $cart, EligibleForDiscount $eligibleForDiscount): bool;

    public function apply(Cart $cart);

    public function discountAmountTotal(Cart $cart): Money;

    public function discountAmount(Cart $cart, EligibleForDiscount $eligibleForDiscount): Money;

    public function usesCondition(string $condition_key): bool;

    /**
     * @return Condition[]
     */
    public function conditions(): array;

    public function condition(ConditionKey $conditionKey): ?Condition;

    public function isCombinable(): bool;

    public function data($key = null);
}
