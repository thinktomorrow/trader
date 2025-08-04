<?php

namespace Thinktomorrow\Trader\Application\Product\ProductTaxa;

interface VariantTaxonRead extends Taxon
{
    public function getVariantId(): string;
}
