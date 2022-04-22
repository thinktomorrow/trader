<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

class GridRepositoryTest
{
    /** @test */
    public function it_only_fetches_grid_products()
    {
        $this->createCatalog();

        $productGroups = app()->make(GridRepository::class)->getResults();

        $this->assertGridCounts($productGroups, 2, 3);
    }

    /** @test */
    public function it_can_filter_by_minimum_sale_price()
    {
        $this->createCatalog();

        $productGroups = app()->make(GridRepository::class)->filterByPrice(Money::EUR(250))->getResults();

        $this->assertGridCounts($productGroups, 1, 1);
        $this->assertEquals(Money::EUR(300), $productGroups->first()->getGridProducts()->first()->getTotal());
    }

    /** @test */
    public function it_can_filter_by_maximum_sale_price()
    {
        $this->createCatalog();

        $productGroups = app()->make(GridRepository::class)->filterByPrice(null, Money::EUR(250))->getResults();

        $this->assertGridCounts($productGroups, 1, 2);
        $this->assertEquals(Money::EUR(80), $productGroups->first()->getGridProducts()->first()->getTotal());
    }

    /** @test */
    public function it_can_filter_by_sale_price_range()
    {
        $this->createCatalog();

        $productGroups = app()->make(GridRepository::class)->filterByPrice(Money::EUR(80), Money::EUR(90))->getResults();

        $this->assertGridCounts($productGroups, 1, 1);
        $this->assertEquals(Money::EUR(80), $productGroups->first()->getGridProducts()->first()->getTotal());
    }

    /** @test */
    public function it_can_filter_by_exact_search_term()
    {
        $this->createCatalog();

        $productGroups = app()->make(GridRepository::class)->filterByTerm('deluxe')->getResults();

        $this->assertGridCounts($productGroups, 1, 1);
    }

    /** @test */
    public function it_can_filter_by_partial_search_term()
    {
        $this->disableExceptionHandling();
        $this->createCatalog();

        $productGroups = app()->make(GridRepository::class)->filterByTerm('large')->getResults();

        $this->assertGridCounts($productGroups, 2, 2);
    }

    /** @test */
    public function it_can_filter_by_taxonomy()
    {
        $this->createCatalog();

        $productGroups = app()->make(GridRepository::class)->filterByTaxa(['blue'])->getResults();

        $this->assertGridCounts($productGroups, 1, 2);
    }

    /** @test */
    public function when_filtering_taxon_all_child_taxonomy_is_included_in_the_search()
    {
        $this->createCatalog();

        $productGroups = app()->make(GridRepository::class)->filterByTaxa(['yellow'])->getResults();

        $this->assertGridCounts($productGroups, 1, 2);
    }

    /** @test */
    public function it_can_sort_by_sale_price()
    {
        $this->createCatalog();

        $productGroups = app()->make(GridRepository::class)->sortByPrice()->getResults();

        $this->assertGridCounts($productGroups, 2, 3);

        $previousSalePrice = null;
        foreach ($productGroups as $productGroup) {
            $salePrice = $productGroup->getGridProducts()->first()->getTotal();

            if ($previousSalePrice) {
                $this->assertGreaterThanOrEqual($previousSalePrice, $salePrice);
            }

            $previousSalePrice = $salePrice;
        }
    }

    /** @test */
    public function it_can_sort_by_descending_sale_price()
    {
        $this->createCatalog();

        $productGroups = app()->make(GridRepository::class)->sortByPriceDesc()->getResults();

        $this->assertGridCounts($productGroups, 2, 3);

        $previousSalePrice = null;
        foreach ($productGroups as $productGroup) {
            $salePrice = $productGroup->getGridProducts()->first()->getTotal();

            if ($previousSalePrice) {
                $this->assertLessThanOrEqual($previousSalePrice, $salePrice);
            }

            $previousSalePrice = $salePrice;
        }
    }

    /** @test */
    public function it_can_sort_by_product_label()
    {
        $this->createCatalog();

        $productGroups = app()->make(GridRepository::class)->sortByLabel()->getResults();

        $this->assertGridCounts($productGroups, 2, 3);

        $labels = $productGroups->map(fn ($group) => $group->getGridProducts()->first()->getTitle());

        $expected = $labels->toArray();
        natcasesort($expected);

        $this->assertEquals($expected, $labels->toArray());
    }

    /** @test */
    public function it_can_sort_by_descending_label()
    {
        $this->createCatalog();

        $productGroups = app()->make(GridRepository::class)->sortByLabelDesc()->getResults();

        $this->assertGridCounts($productGroups, 2, 3);

        $labels = $productGroups->map(fn ($group) => $group->getGridProducts()->first()->getTitle());

        $expected = $labels->toArray();
        natcasesort($expected);

        $this->assertEquals(array_reverse($expected), $labels->toArray());
    }
}
