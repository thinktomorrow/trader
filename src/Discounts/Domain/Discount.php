<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Contracts\HasType;
use Thinktomorrow\Trader\Orders\Domain\Order;

interface Discount extends HasType
{
    public function id(): DiscountId;

    public function getBaseType(): string;

    public function getBase(Order $order): EligibleForDiscount;

    public function getType(): string;

    public function applicable(Order $order, EligibleForDiscount $eligibleForDiscount): bool;

    public function apply(Order $order, EligibleForDiscount $eligibleForDiscount);

    public function discountAmount(Order $order, EligibleForDiscount $eligibleForDiscount): Money;

    public function discountBasePrice(Order $order, EligibleForDiscount $eligibleForDiscount): Money;

    public function usesCondition(string $condition_key): bool;

    public function data($key = null);
}
