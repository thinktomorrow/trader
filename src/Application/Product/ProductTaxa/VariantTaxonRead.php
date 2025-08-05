<?php

namespace Thinktomorrow\Trader\Application\Product\ProductTaxa;

interface VariantTaxonRead extends ProductTaxonRead
{
    public function getVariantId(): string;
}
