<?php

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Vine\DefaultNode;
use Thinktomorrow\Vine\NodeCollection;
use Thinktomorrow\Vine\NodeCollectionFactory;
use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonFilter;

trait UsesTaxonFilterTree
{
    private ?NodeCollection $tree = null;

    private function getTree(): NodeCollection
    {
        if($this->tree) {
            return $this->tree;
        }

        $this->tree = (new NodeCollectionFactory)->strict()->fromSource(
            new TaxonSource(
                $this->taxonTreeRepository->getAllTaxonFilters(),
                fn (TaxonFilter $taxonFilter) => new DefaultNode($taxonFilter)
            )
        );

        return $this->tree;
    }
}
