<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Filter;

interface TaxonFilterTreeComposer
{
    public function getAvailableFilters(string $mainTaxonFilterKey): TaxonFilters;

    public function getActiveFilters(string $mainTaxonFilterKey, array $activeKeys): TaxonFilters;
}
