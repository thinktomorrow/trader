<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Assert\Assertion;
use Thinktomorrow\Trader\Common\Domain\UniqueCollection;

class AppliedDiscountCollection extends UniqueCollection
{
    protected function assertItem($item)
    {
        Assertion::isInstanceOf($item, AppliedDiscount::class);
    }
}
