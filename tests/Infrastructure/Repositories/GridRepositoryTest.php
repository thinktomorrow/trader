<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Money\Money;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Product\Grid\GridItem;
use Thinktomorrow\Trader\Application\Product\ProductApplication;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Application\Taxon\TaxonApplication;
use Thinktomorrow\Trader\Application\Taxonomy\TaxonomyApplication;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultGridItem;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlGridRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;
use Thinktomorrow\Trader\Infrastructure\Vine\VineFlattenedTaxonIdsComposer;

class GridRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createMysqlCatalog();
    }

    public function test_it_can_fetch_grid_item()
    {
        $gridItems = $this->getMysqlGridRepository()->getResults();

        /** @var GridItem $gridItem */
        $gridItem = $gridItems->first();

        $this->assertNotEmpty($gridItem->getSalePrice());
        $this->assertNotEmpty($gridItem->getUnitPrice());
        $this->assertNotEmpty($gridItem->getUrl());
        $this->assertNotEmpty($gridItem->getTitle());
    }

    public function test_it_can_fetch_taxa_per_grid_item()
    {
        $gridItems = $this->getMysqlGridRepository()->getResults();

        /** @var GridItem $gridItem */
        $gridItem = $gridItems->first();

        $this->assertNotEmpty($gridItem->getTaxa());
        $this->assertInstanceOf(ProductTaxonItem::class, $gridItem->getTaxa()[0]);

        $this->assertEquals('foobar', $gridItem->getTaxa()[0]->getKey('nl'));
        $this->assertEquals('foobar', $gridItem->getTaxa()[0]->getUrl('nl'));
        $this->assertEquals('foobar nl', $gridItem->getTaxa()[0]->getLabel('nl'));

        $this->assertEquals('foobar', $gridItem->getTaxa()[0]->getKey('en'));
        $this->assertEquals('foobar', $gridItem->getTaxa()[0]->getUrl('en'));
        $this->assertEquals('foobar en', $gridItem->getTaxa()[0]->getLabel('en'));
    }

    public function test_it_can_fetch_taxa_that_are_shown_in_grid(): void
    {
        $gridItems = $this->getMysqlGridRepository()->getResults();

        /** @var GridItem $gridItem */
        $gridItem = $gridItems->first();

        $this->assertCount(1, array_filter($gridItem->getTaxa(), fn (ProductTaxonItem $taxon) => $taxon->showsInGrid()));
        $this->assertCount(0, $gridItem->getGridCategories());
        $this->assertCount(1, $gridItem->getGridProductProperties());
        $this->assertCount(0, $gridItem->getGridVariantProperties());
        $this->assertCount(0, $gridItem->getGridCollections());
        $this->assertCount(0, $gridItem->getGridTags());


    }

    public function test_it_only_fetches_grid_products()
    {
        $gridItems = $this->getMysqlGridRepository()->getResults();

        $this->assertCount(3, $gridItems);
    }

    public function test_it_can_filter_by_minimum_sale_price()
    {
        $gridItems = $this->getMysqlGridRepository()->filterByPrice('251')->getResults();

        $this->assertCount(1, $gridItems);
        $this->assertTrue($gridItems->first()->getSalePriceAsMoney()->greaterThan(Money::EUR(251)));
    }

    public function test_it_can_filter_by_maximum_sale_price()
    {
        $gridItems = $this->getMysqlGridRepository()->filterByPrice(null, '251')->getResults();

        $this->assertCount(2, $gridItems);
        $this->assertTrue($gridItems[0]->getSalePriceAsMoney()->lessThan(Money::EUR(251)));
        $this->assertTrue($gridItems[1]->getSalePriceAsMoney()->lessThan(Money::EUR(251)));
    }

    public function test_it_can_filter_by_price_range()
    {
        $gridItems = $this->getMysqlGridRepository()->filterByPrice('101', '251')->getResults();

        $this->assertCount(1, $gridItems);
        $this->assertTrue($gridItems[0]->getSalePriceAsMoney()->greaterThan(Money::EUR(101)));
        $this->assertTrue($gridItems[0]->getSalePriceAsMoney()->lessThan(Money::EUR(251)));
    }

    public function test_it_can_filter_by_exact_search_term()
    {
        $gridItems = $this->getMysqlGridRepository()->filterByTerm('product one')->getResults();

        $this->assertCount(1, $gridItems);
        $this->assertEquals('product one', $gridItems->first()->getTitle());
    }

    public function test_it_can_filter_by_partial_search_term()
    {
        $gridItems = $this->getMysqlGridRepository()->filterByTerm('one')->getResults();

        $this->assertCount(1, $gridItems);
        $this->assertEquals('product one', $gridItems->first()->getTitle());
    }

    public function test_it_can_filter_by_taxon()
    {
        $gridItems = $this->getMysqlGridRepository()->filterByTaxonKeys(['foobar-child'])->getResults();

        $this->assertCount(1, $gridItems);
    }

    public function test_when_filtering_taxon_all_child_taxa_are_included_in_the_search()
    {
        $gridItems = $this->getMysqlGridRepository()->filterByTaxonKeys(['foobar'])->getResults();

        $this->assertCount(2, $gridItems);
    }

    public function test_it_can_sort_by_sale_price()
    {
        $gridItems = $this->getMysqlGridRepository()->sortByPrice()->getResults();

        $this->assertCount(3, $gridItems);

        $previousSalePrice = null;
        foreach ($gridItems as $gridItem) {
            $salePrice = $gridItem->getSalePriceAsMoney()->getAmount();

            if ($previousSalePrice) {
                $this->assertGreaterThanOrEqual($previousSalePrice, $salePrice);
            }

            $previousSalePrice = $salePrice;
        }
    }

    public function test_it_can_sort_by_descending_sale_price()
    {
        $gridItems = $this->getMysqlGridRepository()->sortByPriceDesc()->getResults();

        $this->assertCount(3, $gridItems);

        $previousSalePrice = null;
        foreach ($gridItems as $gridItem) {
            $salePrice = $gridItem->getSalePriceAsMoney()->getAmount();

            if ($previousSalePrice) {
                $this->assertLessThanOrEqual($previousSalePrice, $salePrice);
            }

            $previousSalePrice = $salePrice;
        }
    }

    public function test_it_can_sort_by_product_title()
    {
        $gridItems = $this->getMysqlGridRepository()->sortByLabel()->getResults();

        $this->assertCount(3, $gridItems);

        $titles = $gridItems->map(fn ($gridItem) => $gridItem->getTitle());

        $expected = $titles->toArray();
        natcasesort($expected);

        $this->assertEquals($expected, $titles->toArray());
    }

    public function test_it_can_sort_by_descending_label()
    {
        $gridItems = $this->getMysqlGridRepository()->sortByLabelDesc()->getResults();

        $this->assertCount(3, $gridItems);

        $titles = $gridItems->map(fn ($gridItem) => $gridItem->getTitle());

        $expected = $titles->toArray();
        natcasesort($expected);

        $this->assertEquals(array_reverse($expected), $titles->toArray());
    }

    protected function createMysqlCatalog()
    {
        $taxonApplication = new TaxonApplication(
            new TestTraderConfig(),
            new EventDispatcherSpy(),
            new MysqlTaxonRepository(),
        );

        $this->createCatalog(
            new TaxonomyApplication(new TestTraderConfig(), new EventDispatcherSpy(), new MysqlTaxonomyRepository()),
            $taxonApplication,
            new ProductApplication(
                new TestTraderConfig(),
                new EventDispatcherSpy(),
                new MysqlProductRepository(new MysqlVariantRepository(new TestContainer())),
                new MysqlVariantRepository(new TestContainer()),
            ),
            new MysqlProductRepository(new MysqlVariantRepository(new TestContainer()))
        );
    }

    private function getMysqlGridRepository()
    {
        (new TestContainer())->add(GridItem::class, DefaultGridItem::class);

        return new MysqlGridRepository(
            new TestContainer(),
            new TestTraderConfig(),
            new VineFlattenedTaxonIdsComposer(new MysqlTaxonTreeRepository(new TestContainer(), new TestTraderConfig()))
        );
    }
}
