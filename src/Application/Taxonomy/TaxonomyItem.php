<?php

namespace Thinktomorrow\Trader\Application\Taxonomy;

interface TaxonomyItem
{
    public static function fromMappedData(array $state): static;

    public function getTaxonomyId(): string;

    public function getTaxonomyType(): string;

    /** Taxon|Taxonomy can be set offline or such */
    public function showOnline(): bool;

    public function getLabel(?string $locale = null): string;
}
