<?php

namespace Thinktomorrow\Trader\Application\Taxon\Tree;

interface TaxonTreeRepository
{
    public function getTree(): TaxonTree;
}
