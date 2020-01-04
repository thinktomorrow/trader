<?php

namespace Thinktomorrow\Trader\Find\Catalog\Reads;

interface ProductRepository
{
    public function channel(string $channel);

    public function locale(string $locale);

    public function findById($id): Product;
}
