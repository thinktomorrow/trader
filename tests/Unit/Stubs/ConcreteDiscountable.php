<?php

namespace Thinktomorrow\Trader\Tests\Unit\Stubs;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;

class ConcreteDiscountable
{
    public function __construct()
    {

    }

    public function discounts(): AppliedDiscountCollection
    {
        // TODO: Implement discounts() method.
    }

    public function addDiscount(AppliedDiscount $discount)
    {
        // TODO: Implement addDiscount() method.
    }

    public function discountTotal(): Money
    {
        // TODO: Implement discountTotal() method.
    }

    public function addToDiscountTotal(Money $addition)
    {
        // TODO: Implement addToDiscountTotal() method.
    }
}