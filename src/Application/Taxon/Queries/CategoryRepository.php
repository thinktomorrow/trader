<?php

namespace Thinktomorrow\Trader\Application\Taxon\Queries;

use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;

interface CategoryRepository
{
    public function findTaxonByKey(string $key): TaxonNode;
}
