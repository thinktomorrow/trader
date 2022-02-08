<?php declare(strict_types=1);

namespace Purchase\Discounts\Domain;

use Money\Money;
use Purchase\Cart\Domain\Cart;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\ConditionKey;

interface Discount
{
    public function id(): DiscountId;

    public function discountType(): DiscountType;

    public function discountFamily(): DiscountFamily;

    public function applicableAtLeastOnce(Cart $cart): bool;

    public function applicable(Cart $cart, Discountable $eligibleForDiscount): bool;

    public function apply(Cart $cart);

    public function discountAmountTotal(Cart $cart): Money;

    public function discountAmount(Cart $cart, Discountable $eligibleForDiscount): Money;

    public function usesCondition(string $condition_key): bool;

    /**
     * @return Condition[]
     */
    public function conditions(): array;

    public function condition(ConditionKey $conditionKey): Condition;

    /**
     * @return bool
     */
    public function isCombinable(): bool;
}
