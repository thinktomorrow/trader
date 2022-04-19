<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Filter;

use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;

interface TaxonFilterTreeComposer
{
    public function getAvailableFilters(string $mainTaxonFilterKey): TaxonTree;

    public function getActiveFilters(string $mainTaxonFilterKey, array $activeKeys): TaxonTree;
}
