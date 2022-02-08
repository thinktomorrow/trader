<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Assert\Assertion;
use Thinktomorrow\Trader\Discounts\Domain\Condition;
use Thinktomorrow\Trader\Discounts\Domain\Discountable;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Order\Domain\Order;

abstract class BaseDiscount
{
    protected DiscountId $id;

    /** @var Condition[] */
    protected array $conditions;

    protected array $data;

    public function __construct(DiscountId $id, array $conditions, array $data = [])
    {
        Assertion::allIsInstanceOf($conditions, Condition::class);

        $this->id = $id;
        $this->conditions = $conditions;

        // Custom data, e.g. discount text for display on site or shopping cart
        $this->data = $data;
    }

    public function id(): DiscountId
    {
        return $this->id;
    }

    abstract public function getType(): string;

    /**
     * Check if the discount is somehow applicable to the cart order or at least a part of it.
     *
     * @param Order $order
     * @return bool
     */
    public function overallApplicable(Order $order): bool
    {
        return $this->applicable($order, $order);
    }

    public function applicable(Order $order, Discountable $discountable): bool
    {
        if ($this->greaterThanPrice($order, $discountable) || $this->discountAmountBelowZero($order, $discountable)) {
            return false;
        }

        foreach ($this->conditions as $condition) {
            if (false == $condition->check($order, $discountable)) {
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
        if (! $key) {
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
        foreach ($this->conditions as $condition) {
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

    private function greaterThanPrice(Order $order, Discountable $eligibleForDiscount): bool
    {
        // Protect against negative overflow where total would dive under zero - Discount total cannot be higher than discount base.
        $discountTotal = $eligibleForDiscount->discountTotalAsMoney()->add($this->discountAmount($order, $eligibleForDiscount));

        return $discountTotal->greaterThan($eligibleForDiscount->discountBasePriceAsMoney($this->conditions));
    }

    private function discountAmountBelowZero(Order $order, Discountable $eligibleForDiscount): bool
    {
        $discountTotal = $eligibleForDiscount->discountTotalAsMoney()->add($this->discountAmount($order, $eligibleForDiscount));

        return $discountTotal->isNegative();
    }
}
