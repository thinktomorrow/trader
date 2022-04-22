<?php

namespace Thinktomorrow\Trader\Application\Taxon\Category;

use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;

interface CategoryRepository
{
    public function findTaxonByKey(string $key): TaxonNode;
}
