<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Discounts\Domain;

use Money\Money;
use Thinktomorrow\Trader\Order\Domain\Order;

interface Discount
{
    public function getId(): DiscountId;

    public function discountType(): DiscountType;

    public function discountFamily(): DiscountFamily;

    public function applicableAtLeastOnce(Order $cart): bool;

    public function applicable(Order $cart, Discountable $eligibleForDiscount): bool;

    public function apply(Order $cart);

    public function discountAmountTotal(Order $cart): Money;

    public function discountAmount(Order $cart, Discountable $eligibleForDiscount): Money;

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
