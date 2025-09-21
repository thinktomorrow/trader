<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Queries;

interface TaxaSelectOptions
{
    public function getByTaxonomy(string $taxonomyId): array;

    public function getForMultiselectByTaxonomy(string $taxonomyId): array;

    public function excludeTaxa(array|string $excludeTaxonIds): static;
}
