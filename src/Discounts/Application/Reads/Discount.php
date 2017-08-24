<?php

namespace Thinktomorrow\Trader\Discounts\Application\Reads;

interface Discount
{
    public function description(): string;

    public function amount(): string;
}