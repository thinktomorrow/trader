<?php

namespace Thinktomorrow\Trader\Application\Product\ProductTaxa;

interface ProductTaxonRead
{
    public static function fromMappedData(array $state): static;

    public function getProductId(): string;

    public function getTaxonomyId(): string;

    public function getTaxonomyType(): string;

    public function getTaxonId(): string;

    public function getOrder(): int;

    /** Taxon|Taxonomy can be set offline or such */
    public function showOnline(): bool;

    /** Display this info on the grid listing item */
    public function showsInGrid(): bool;

    //    public function getKey(?string $locale = null): ?string;

    public function getLabel(?string $locale = null): string;
}
