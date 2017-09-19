<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Read;

interface Discount
{
    public function description(): string;

    public function amount(): string;
}
