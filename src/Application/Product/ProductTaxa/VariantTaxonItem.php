<?php

namespace Thinktomorrow\Trader\Application\Product\ProductTaxa;

interface VariantTaxonItem extends ProductTaxonItem
{
    public function getVariantId(): string;
}
