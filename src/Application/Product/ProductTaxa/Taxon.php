<?php

namespace Thinktomorrow\Trader\Application\Product\ProductTaxa;

/**
 * @internal
 * This interface is not intended to be implemented directly.
 * Instead, extend one of the public interfaces:
 * ProductTaxon, VariantTaxon.
 */
interface Taxon
{
    public static function fromMappedData(array $state): static;

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
