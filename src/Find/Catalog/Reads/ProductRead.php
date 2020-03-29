<?php

namespace Thinktomorrow\Trader\Find\Catalog\Reads;

interface ProductRead
{
    public function id();

    public function data($key, $default = null);

    public function salePrice(): string;

    public function price(): string;

    public function url(): string;

    public function buyUrl(): string;
}
