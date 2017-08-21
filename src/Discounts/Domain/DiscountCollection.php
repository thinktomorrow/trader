<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Assert\Assertion;
use Thinktomorrow\Trader\Common\Domain\UniqueCollection;

class DiscountCollection extends UniqueCollection
{
    protected function assertItem($item)
    {
        Assertion::isInstanceOf($item, Discount::class);
    }
}
