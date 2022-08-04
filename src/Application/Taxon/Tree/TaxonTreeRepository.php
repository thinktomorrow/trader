<?php

namespace Thinktomorrow\Trader\Application\Taxon\Tree;

interface TaxonTreeRepository
{
    public function findTaxonById(string $taxonId): TaxonNode;

    public function findTaxonByKey(string $key): TaxonNode;

    public function getTree(): TaxonTree;
}
