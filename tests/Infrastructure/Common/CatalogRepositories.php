<?php

namespace Tests\Infrastructure\Common;

use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonFilterTreeComposer;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonRepository;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyRepository;

interface CatalogRepositories
{
    public function taxonomyRepository(): TaxonomyRepository;

    public function taxonRepository(): TaxonRepository;

    public function taxonTreeRepository(): TaxonTreeRepository;

    public function productRepository(): ProductRepository;

    public function filterTreeComposer(): TaxonFilterTreeComposer;
}
