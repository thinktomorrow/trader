<?php

namespace Tests\Infrastructure\Common;

use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonFilterTreeComposer;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonRepository;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCountryRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPromoRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVatRateRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;
use Thinktomorrow\Trader\Infrastructure\Vine\VineTaxonFilterTreeComposer;

class InMemoryCatalogRepositories implements CatalogRepositories
{
    public static function clear(): void
    {
        InMemoryTaxonomyRepository::clear();
        InMemoryTaxonRepository::clear();
        InMemoryProductRepository::clear();

        InMemoryOrderRepository::clear();
        InMemoryProductRepository::clear();
        InMemoryVariantRepository::clear();
        InMemoryTaxonRepository::clear();
        InMemoryPromoRepository::clear();
        InMemoryCountryRepository::clear();
        InMemoryVatRateRepository::clear();
    }

    public function taxonomyRepository(): TaxonomyRepository
    {
        return new InMemoryTaxonomyRepository();
    }

    public function taxonRepository(): TaxonRepository
    {
        return new InMemoryTaxonRepository();
    }

    public function taxonTreeRepository(): TaxonTreeRepository
    {
        return new InMemoryTaxonTreeRepository(new TestContainer(), new TestTraderConfig());
    }

    public function productRepository(): ProductRepository
    {
        return new InMemoryProductRepository();
    }

    public function filterTreeComposer(): TaxonFilterTreeComposer
    {
        return new VineTaxonFilterTreeComposer(new TestTraderConfig(), $this->taxonTreeRepository(), $this->taxonomyRepository());
    }
}
