<?php

namespace Thinktomorrow\Trader\Application\Product\Taxa;

interface ProductTaxonItem
{
    public static function fromMappedData(array $state, array $keys): static;

    public function getProductId(): string;

    public function getTaxonomyId(): string;

    public function getTaxonomyType(): string;

    public function getTaxonId(): string;

    /** Taxon|Taxonomy can be set offline or such */
    public function showOnline(): bool;

    /** Display this info on the grid listing item */
    public function showsInGrid(): bool;

    public function getKey(?string $locale = null): ?string;

    public function getUrl(?string $locale = null): string;

    public function getLabel(?string $locale = null): string;

    public function getTaxonomyLabel(?string $locale = null): string;
}
