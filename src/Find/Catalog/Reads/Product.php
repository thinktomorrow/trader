<?php

namespace Thinktomorrow\Trader\Find\Catalog\Reads;

interface Product
{
    public function id();

    public function data($key, $default = null);

    public function set($key, $value): self;
}
