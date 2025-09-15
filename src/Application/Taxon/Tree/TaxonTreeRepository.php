<?php

namespace Thinktomorrow\Trader\Application\Taxon\Tree;

use Thinktomorrow\Trader\Domain\Common\Locale;

interface TaxonTreeRepository
{
    public function findTaxonById(string $taxonId): TaxonNode;

    public function findTaxonByKey(string $key): TaxonNode;

    public function getTree(): TaxonTree;

    public function getTreeByTaxonomy(string $taxonomyId): TaxonTree;

    public function getTreeByTaxonomies(array $taxonomyIds): TaxonTree;

    public function setLocale(Locale $locale): static;
}
