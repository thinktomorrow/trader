<?php

namespace Thinktomorrow\Trader\Application\Product\ProductTaxa;

interface ProductTaxonRepository
{
    /** @return array<ProductTaxonRead> */
    public function getTaxaByProduct(string $productId): array;
}
