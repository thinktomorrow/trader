<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;

final class TaxonTreeWithProductCountRepositoryTest extends TestCase
{
    public function test_it_can_get_total_of_products()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->createDefaultTree();
            $product = $catalog->createProduct();

            $catalog->linkProductToTaxon($product, 'taxon-aaa');

            $repository = $catalog->repos()->taxonTreeRepository();

            $taxonNode = $repository->findTaxonById('taxon-aaa');

            $this->assertCount(1, $taxonNode->getProductIds());
            $this->assertCount(1, $taxonNode->getGridProductIds());
            $this->assertEquals(1, $taxonNode->getProductTotal());
        }
    }

    public function test_it_can_get_count_of_products()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->createDefaultTree();
            $product = $catalog->createProduct();

            $catalog->linkProductToTaxon($product, 'taxon-aaa');

            $repository = $catalog->repos()->taxonTreeRepository();

            $taxonNode = $repository->findTaxonById('taxon-aaa');

            $this->assertEquals(1, $taxonNode->getProductCount([$product->productId->get()]));
            $this->assertEquals(0, $taxonNode->getProductCount(['non-existing-product-id']));
        }
    }
}
