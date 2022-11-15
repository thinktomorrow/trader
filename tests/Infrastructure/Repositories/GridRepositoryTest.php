<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Money\Money;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Product\Grid\GridItem;
use Thinktomorrow\Trader\Application\Product\ProductApplication;
use Thinktomorrow\Trader\Application\Taxon\TaxonApplication;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultGridItem;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlGridRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
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

    /** @test */
    public function it_can_fetch_grid_item()
    {
        $gridItems = $this->getMysqlGridRepository()->getResults();

        /** @var GridItem $gridItem */
        $gridItem = $gridItems->first();

        $this->assertNotEmpty($gridItem->getSalePrice());
        $this->assertNotEmpty($gridItem->getUnitPrice());
        $this->assertNotEmpty($gridItem->getUrl());
        $this->assertNotEmpty($gridItem->getTitle());
        $this->assertNotEmpty($gridItem->getTaxonIds());
    }

    /** @test */
    public function it_only_fetches_grid_products()
    {
        $gridItems = $this->getMysqlGridRepository()->getResults();

        $this->assertCount(3, $gridItems);
    }

    /** @test */
    public function it_can_filter_by_minimum_sale_price()
    {
        $gridItems = $this->getMysqlGridRepository()->filterByPrice('251')->getResults();

        $this->assertCount(1, $gridItems);
        $this->assertTrue($gridItems->first()->getSalePriceAsMoney()->greaterThan(Money::EUR(251)));
    }

    /** @test */
    public function it_can_filter_by_maximum_sale_price()
    {
        $gridItems = $this->getMysqlGridRepository()->filterByPrice(null, '251')->getResults();

        $this->assertCount(2, $gridItems);
        $this->assertTrue($gridItems[0]->getSalePriceAsMoney()->lessThan(Money::EUR(251)));
        $this->assertTrue($gridItems[1]->getSalePriceAsMoney()->lessThan(Money::EUR(251)));
    }

    /** @test */
    public function it_can_filter_by_price_range()
    {
        $gridItems = $this->getMysqlGridRepository()->filterByPrice('101', '251')->getResults();

        $this->assertCount(1, $gridItems);
        $this->assertTrue($gridItems[0]->getSalePriceAsMoney()->greaterThan(Money::EUR(101)));
        $this->assertTrue($gridItems[0]->getSalePriceAsMoney()->lessThan(Money::EUR(251)));
    }

    /** @test */
    public function it_can_filter_by_exact_search_term()
    {
        $gridItems = $this->getMysqlGridRepository()->filterByTerm('product one')->getResults();

        $this->assertCount(1, $gridItems);
        $this->assertEquals('product one', $gridItems->first()->getTitle());
    }

    /** @test */
    public function it_can_filter_by_partial_search_term()
    {
        $gridItems = $this->getMysqlGridRepository()->filterByTerm('one')->getResults();

        $this->assertCount(1, $gridItems);
        $this->assertEquals('product one', $gridItems->first()->getTitle());
    }

    /** @test */
    public function it_can_filter_by_taxonomy()
    {
        $gridItems = $this->getMysqlGridRepository()->filterByTaxonKeys(['foobar-child'])->getResults();

        $this->assertCount(1, $gridItems);
    }

    /** @test */
    public function when_filtering_taxon_all_child_taxonomy_is_included_in_the_search()
    {
        $gridItems = $this->getMysqlGridRepository()->filterByTaxonKeys(['foobar'])->getResults();

        $this->assertCount(2, $gridItems);
    }

    /** @test */
    public function it_can_sort_by_sale_price()
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

    /** @test */
    public function it_can_sort_by_descending_sale_price()
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

    /** @test */
    public function it_can_sort_by_product_title()
    {
        $gridItems = $this->getMysqlGridRepository()->sortByLabel()->getResults();

        $this->assertCount(3, $gridItems);

        $titles = $gridItems->map(fn ($gridItem) => $gridItem->getTitle());

        $expected = $titles->toArray();
        natcasesort($expected);

        $this->assertEquals($expected, $titles->toArray());
    }

    /** @test */
    public function it_can_sort_by_descending_label()
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
        $this->createCatalog(
            new TaxonApplication(
                new TestTraderConfig(),
                new EventDispatcherSpy(),
                new MysqlTaxonRepository(),
            ),
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
