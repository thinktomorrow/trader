<?php

namespace Thinktomorrow\Trader\Find\Catalog\Domain;

interface ProductRepository
{
    public function findById(ProductId $productId): Product;
}
