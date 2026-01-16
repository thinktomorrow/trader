<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Money\Money;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Product\Grid\GridItem;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;

class GridRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $catalog = CatalogContext::mysql();

        $catalog->createTaxonomy();
        $taxon = $catalog->createTaxon();
        $taxonChild = $catalog->createTaxon('taxon-bbb', 'taxonomy-aaa', $taxon->taxonId->get());
        $taxonChild2 = $catalog->createTaxon('taxon-ccc', 'taxonomy-aaa', $taxon->taxonId->get());

        $product = $catalog->createProduct();

        $productB = $catalog->createProduct('product-bbb', null);
        $variantB = $catalog->createVariant($productB->productId->get(), 'variant-bbb', ['show_in_grid' => false]);

        $productC = $catalog->createProduct('product-ccc', null);
        $variantC = $catalog->createVariant($productC->productId->get(), 'variant-ccc', [
            'unit_price' => 200,
            'sale_price' => 160,
            'includes_vat' => true,
        ]);

        $catalog->linkProductToTaxon($product->productId->get(), $taxonChild->taxonId->get());
        $catalog->linkProductToTaxon($productB->productId->get(), $taxonChild->taxonId->get());
        $catalog->linkProductToTaxon($productC->productId->get(), $taxonChild2->taxonId->get());
    }

    public function test_it_can_fetch_grid_item()
    {
        $gridItems = CatalogContext::mysql()->repos()->gridRepository()->getResults();

        /** @var GridItem $gridItem */
        $gridItem = $gridItems->first();

        $this->assertNotEmpty($gridItem->getSalePrice());
        $this->assertNotEmpty($gridItem->getUnitPrice());
        $this->assertNotEmpty($gridItem->getUrl());
        $this->assertNotEmpty($gridItem->getTitle());
    }

    public function test_it_can_fetch_taxa_per_grid_item()
    {
        $gridItems = CatalogContext::mysql()->repos()->gridRepository()->getResults();

        /** @var GridItem $gridItem */
        $gridItem = $gridItems->first();

        $this->assertNotEmpty($gridItem->getTaxa());
        $this->assertInstanceOf(ProductTaxonItem::class, $gridItem->getTaxa()[0]);

        $this->assertEquals('taxon-bbb-key-nl', $gridItem->getTaxa()[0]->getKey('nl'));
        $this->assertEquals('taxon-bbb-key-nl', $gridItem->getTaxa()[0]->getUrl('nl'));
        $this->assertEquals('taxon-bbb title nl', $gridItem->getTaxa()[0]->getLabel('nl'));

        $this->assertEquals('taxon-bbb-key-fr', $gridItem->getTaxa()[0]->getKey('fr'));
        $this->assertEquals('taxon-bbb-key-fr', $gridItem->getTaxa()[0]->getUrl('fr'));
        $this->assertEquals('taxon-bbb title fr', $gridItem->getTaxa()[0]->getLabel('fr'));
    }

    public function test_it_can_fetch_taxa_that_are_shown_in_grid(): void
    {
        $gridItems = CatalogContext::mysql()->repos()->gridRepository()->getResults();

        /** @var GridItem $gridItem */
        $gridItem = $gridItems->first();

        $this->assertCount(1, array_filter($gridItem->getTaxa(), fn(ProductTaxonItem $taxon) => $taxon->showsInGrid()));
        $this->assertCount(1, $gridItem->getGridCategories());
        $this->assertCount(0, $gridItem->getGridProductProperties());
        $this->assertCount(0, $gridItem->getGridVariantProperties());
        $this->assertCount(0, $gridItem->getGridCollections());
        $this->assertCount(0, $gridItem->getGridTags());


    }

    public function test_it_only_fetches_grid_products()
    {
        $gridItems = CatalogContext::mysql()->repos()->gridRepository()->getResults();

        $this->assertCount(2, $gridItems);
    }

    public function test_it_can_filter_by_minimum_sale_price()
    {
        $gridItems = CatalogContext::mysql()->repos()->gridRepository()->filterByPrice('159')->getResults();

        $this->assertCount(1, $gridItems);
        $this->assertTrue($gridItems->first()->getSalePrice()->getIncludingVat()->equals(Money::EUR(160)));
    }

    public function test_it_can_filter_by_maximum_sale_price()
    {
        $gridItems = CatalogContext::mysql()->repos()->gridRepository()->filterByPrice(null, '101')->getResults();

        $this->assertCount(1, $gridItems);
        $this->assertTrue($gridItems[0]->getSalePrice()->getExcludingVat()->lessThan(Money::EUR(101)));
    }

    public function test_it_can_filter_by_price_range()
    {
        $gridItems = CatalogContext::mysql()->repos()->gridRepository()->filterByPrice('79', '81')->getResults();

        $this->assertCount(1, $gridItems);
        $this->assertTrue($gridItems[0]->getSalePrice()->getExcludingVat()->greaterThan(Money::EUR(79)));
        $this->assertTrue($gridItems[0]->getSalePrice()->getExcludingVat()->lessThan(Money::EUR(81)));
    }

    public function test_it_can_filter_by_exact_search_term()
    {
        $gridItems = CatalogContext::mysql()->repos()->gridRepository()->filterByTerm('product-aaa title nl')->getResults();

        $this->assertCount(1, $gridItems);
        $this->assertEquals('product-aaa title nl', $gridItems->first()->getTitle());
    }

    public function test_it_can_filter_by_partial_search_term()
    {
        $gridItems = CatalogContext::mysql()->repos()->gridRepository()->filterByTerm('title nl')->getResults();

        $this->assertCount(2, $gridItems);

        $gridItems = CatalogContext::mysql()->repos()->gridRepository()->filterByTerm('product-aaa')->getResults();

        $this->assertCount(1, $gridItems);
        $this->assertEquals('product-aaa title nl', $gridItems->first()->getTitle());
    }

    public function test_it_can_filter_by_taxon()
    {
        $gridItems = CatalogContext::mysql()->repos()->gridRepository()->filterByTaxonKeys(['taxon-bbb-key-nl'])->getResults();

        $this->assertCount(1, $gridItems);
        $this->assertEquals('product-aaa', $gridItems->first()->getProductId());
    }

    public function test_when_filtering_unknown_taxon_no_results_are_returned()
    {
        $gridItems = CatalogContext::mysql()->repos()->gridRepository()->filterByTaxonKeys(['unknown'])->getResults();

        $this->assertCount(0, $gridItems);
    }

    public function test_when_filtering_taxon_all_child_taxa_are_included_in_the_search()
    {
        $gridItems = CatalogContext::mysql()->repos()->gridRepository()->filterByTaxonKeys(['taxon-aaa-key-nl'])->getResults();

        $this->assertCount(2, $gridItems);
    }

    public function test_it_can_sort_by_sale_price()
    {
        $gridItems = CatalogContext::mysql()->repos()->gridRepository()->sortByPrice()->getResults();

        $this->assertCount(2, $gridItems);

        $previousSalePrice = null;
        foreach ($gridItems as $gridItem) {
            $salePrice = $gridItem->getSalePrice()->getExcludingVat()->getAmount();

            if ($previousSalePrice) {
                $this->assertGreaterThanOrEqual($previousSalePrice, $salePrice);
            }

            $previousSalePrice = $salePrice;
        }
    }

    public function test_it_can_sort_by_descending_sale_price()
    {
        $gridItems = CatalogContext::mysql()->repos()->gridRepository()->sortByPriceDesc()->getResults();

        $this->assertCount(2, $gridItems);

        $previousSalePrice = null;
        foreach ($gridItems as $gridItem) {
            $salePrice = $gridItem->getSalePrice()->getExcludingVat()->getAmount();

            if ($previousSalePrice) {
                $this->assertLessThanOrEqual($previousSalePrice, $salePrice);
            }

            $previousSalePrice = $salePrice;
        }
    }

    public function test_it_can_sort_by_product_title()
    {
        $gridItems = CatalogContext::mysql()->repos()->gridRepository()->sortByLabel()->getResults();

        $this->assertCount(2, $gridItems);

        $titles = $gridItems->map(fn($gridItem) => $gridItem->getTitle());

        $expected = $titles->toArray();
        natcasesort($expected);

        $this->assertEquals($expected, $titles->toArray());
    }

    public function test_it_can_sort_by_descending_label()
    {
        $gridItems = CatalogContext::mysql()->repos()->gridRepository()->sortByLabelDesc()->getResults();

        $this->assertCount(2, $gridItems);

        $titles = $gridItems->map(fn($gridItem) => $gridItem->getTitle());

        $expected = $titles->toArray();
        natcasesort($expected);

        $this->assertEquals(array_reverse($expected), $titles->toArray());
    }
}
