<?php

namespace Thinktomorrow\Trader\Application\Product\ProductTaxa;

interface ProductTaxonRead extends Taxon
{
    public function getProductId(): string;
}
