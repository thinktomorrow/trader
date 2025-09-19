<?php

namespace Tests\Infrastructure\Common;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonFilterTreeComposer;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonRepository;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;
use Thinktomorrow\Trader\Infrastructure\Vine\VineTaxonFilterTreeComposer;

class MysqlCatalogRepositories implements CatalogRepositories
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function taxonomyRepository(): TaxonomyRepository
    {
        return new MysqlTaxonomyRepository($this->container);
    }

    public function taxonRepository(): TaxonRepository
    {
        return new MysqlTaxonRepository();
    }

    public function taxonTreeRepository(): TaxonTreeRepository
    {
        return new MysqlTaxonTreeRepository(new TestContainer(), new TestTraderConfig());
    }

    public function productRepository(): ProductRepository
    {
        return new MysqlProductRepository(new MysqlVariantRepository(new TestContainer()));
    }

    public function filterTreeComposer(): TaxonFilterTreeComposer
    {
        return new VineTaxonFilterTreeComposer(new TestTraderConfig(), $this->taxonTreeRepository(), $this->taxonomyRepository());
    }
}
