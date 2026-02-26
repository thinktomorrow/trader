<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantProperty;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;

final class VariantRepositoryTest extends TestCase
{
    public function test_it_can_save_and_find_a_variant()
    {
        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {

            // Create taxon data
            $taxonomy = $catalog->createTaxonomy('taxonomy-aaa', TaxonomyType::variant_property->value);
            $taxon = $catalog->createTaxon();
            $product = $catalog->createProduct();
            $variant = $product->getVariants()[0];

            $variantStates = $catalog->repos()->variantRepository()->getStatesByProduct($product->productId);

            $this->assertEquals([$variant], array_map(fn ($variantState) => Variant::fromMappedData($variantState[0], ['product_id' => $product->productId->get()], $variantState[1]), $variantStates));
        }
    }


    public function test_it_can_update_variant_taxa()
    {
        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {

            // Create taxon data
            $catalog->createTaxonomy('taxonomy-aaa', TaxonomyType::variant_property->value);
            $taxon = $catalog->createTaxon();
            $product = $catalog->createProduct();
            $variant = $product->getVariants()[0];

            $variant->updateVariantTaxa([
                VariantProperty::create($variant->variantId, $taxon->taxonId),
            ]);

            $product->updateVariant($variant);
            $catalog->saveProduct($product);

            $variantStates = $catalog->repos()->variantRepository()->getStatesByProduct($product->productId);

            $this->assertEquals([$variant], array_map(fn ($variantState) => Variant::fromMappedData($variantState[0], ['product_id' => $product->productId->get()], $variantState[1]), $variantStates));
        }
    }


    public function test_it_can_delete_an_variant()
    {
        $recordsNotFound = 0;

        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {

            $repository = $catalog->repos()->variantRepository();

            $product = $catalog->createProduct();
            $variant = $product->getVariants()[0];

            $repository->delete($variant->variantId);

            if (count($repository->getStatesByProduct($product->productId)) < 1) {
                $recordsNotFound++;
            }
        }

        $this->assertCount($recordsNotFound, CatalogContext::drivers());
    }

    public function test_it_can_generate_a_next_reference()
    {
        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {

            $repository = $catalog->repos()->variantRepository();

            $this->assertInstanceOf(VariantId::class, $repository->nextReference());
        }
    }

    public function test_it_can_find_all_variants_for_cart()
    {
        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {

            $product = $catalog->createProduct();
            $variant = $product->getVariants()[0];

            $repository = $catalog->repos()->variantRepository();

            $this->assertNotNull($repository->findAllVariantsForCart([$variant->variantId]));
        }
    }
}
