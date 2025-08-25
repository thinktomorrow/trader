<?php

namespace Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa;

interface ProductTaxonRepository
{
    /**
     * Fetch all the taxon states for a given product. This is the raw state,
     * ready to be mapped to a ProductTaxon entity.
     */
    public function getProductTaxonStatesByProduct(string $productId): array;

    /**
     * Fetch all the ProductTaxon entities for the given list of taxa.
     */
    public function getProductTaxaByTaxonIds(string $productId, array $taxonIds): array;
}
