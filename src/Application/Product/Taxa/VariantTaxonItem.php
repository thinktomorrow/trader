<?php

namespace Thinktomorrow\Trader\Application\Product\Taxa;

interface VariantTaxonItem extends ProductTaxonItem
{
    public function getVariantId(): string;
}
