<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Filter;

interface TaxonTreeRepository
{
    public function getAllTaxonFilters(): TaxonFilters;
}
