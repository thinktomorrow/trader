<?php

namespace Thinktomorrow\Trader\Find\Catalog\Reads;

use Money\Money;

interface Product
{
    public function id();

    public function data($key, $default = null);

    public function salePrice(): string;

    public function salePriceAsMoney(): Money;

    public function price(): string;

    public function priceAsMoney(): Money;
}
