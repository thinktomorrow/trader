<?php

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Vine\NodeCollectionFactory;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;

trait UsesTaxonTree
{
    private ?TaxonTree $tree = null;

    private function getTree(): TaxonTree
    {
        if($this->tree) {
            return $this->tree;
        }

        $this->tree = new TaxonTree((new NodeCollectionFactory)->strict()->fromSource(
            new TaxonSource($this->taxonTreeRepository->getAllTaxonNodes())
        )->all());

        return $this->tree;
    }
}
