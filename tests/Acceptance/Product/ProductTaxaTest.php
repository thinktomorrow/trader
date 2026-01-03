<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class ProductTaxaTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_get_a_product_taxon_item()
    {
        $taxonomy = $this->catalogContext->createTaxonomy('taxonomy-aaa', TaxonomyType::variant_property->value);
        $taxonomy2 = $this->catalogContext->createTaxonomy('taxonomy-bbb', TaxonomyType::variant_property->value);
        $taxon = $this->catalogContext->createTaxon();
        $taxon2 = $this->catalogContext->createTaxon('taxon-bbb');
        $taxon3 = $this->catalogContext->createTaxon('taxon-ccc', $taxonomy2->taxonomyId->get());

        $product = $this->catalogContext->createProduct();
        $variantId = $product->getVariants()[0]->variantId;

        $this->catalogContext->linkVariantToTaxon($product->productId->get(), $variantId->get(), $taxon->taxonId->get());
        $this->catalogContext->linkVariantToTaxon($product->productId->get(), $variantId->get(), $taxon2->taxonId->get());
        $this->catalogContext->linkVariantToTaxon($product->productId->get(), $variantId->get(), $taxon3->taxonId->get());

        $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($variantId);

        $taxa = $productDetail->getTaxa();

        $this->assertCount(3, $taxa);
        $this->assertCount(3, $productDetail->getVariantProperties());
        $this->assertCount(0, $productDetail->getProductProperties());
        $this->assertCount(0, $productDetail->getCategories());
        $this->assertCount(0, $productDetail->getGoogleCategories());
        $this->assertCount(0, $productDetail->getTags());
        $this->assertCount(0, $productDetail->getCollections());
    }

    public function test_it_can_get_details_of_taxon_item(): void
    {
        $taxonomy = $this->catalogContext->createTaxonomy('taxonomy-aaa', TaxonomyType::property->value);
        $taxon = $this->catalogContext->createTaxon();

        $product = $this->catalogContext->createProduct();
        $variantId = $product->getVariants()[0]->variantId;

        $this->catalogContext->linkVariantToTaxon($product->productId->get(), $variantId->get(), $taxon->taxonId->get());

        $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($variantId);

        /** @var ProductTaxonItem $taxon */
        $foundTaxon = $productDetail->getProductProperties()[0];

        $this->assertEquals($product->productId->get(), $foundTaxon->getProductId());
        $this->assertEquals($taxon->taxonId, $foundTaxon->getTaxonId());
        $this->assertEquals($taxonomy->taxonomyId, $foundTaxon->getTaxonomyId());
        $this->assertEquals(TaxonomyType::property->value, $foundTaxon->getTaxonomyType());
        $this->assertEquals('taxon-aaa title nl', $foundTaxon->getLabel());
        $this->assertTrue($foundTaxon->showsInGrid());
        $this->assertTrue($foundTaxon->showOnline());
    }

    // Test label, override label with custom relation title, showsInGrid, showOnline
    public function test_it_can_get_label(): void
    {
        $taxonomy = $this->catalogContext->createTaxonomy('taxonomy-aaa', TaxonomyType::property->value);
        $taxon = $this->catalogContext->createTaxon();

        $product = $this->catalogContext->createProduct();
        $variantId = $product->getVariants()[0]->variantId;

        $this->catalogContext->linkVariantToTaxon($product->productId->get(), $variantId->get(), $taxon->taxonId->get());

        $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($variantId);

        /** @var ProductTaxonItem $taxon */
        $taxon = $productDetail->getProductProperties()[0];

        $this->assertEquals('taxon-aaa title nl', $taxon->getLabel());
        $this->assertEquals('taxon-aaa title nl', $taxon->getLabel('nl'));
        $this->assertEquals('taxon-aaa title fr', $taxon->getLabel('fr'));
    }

    public function test_it_can_get_custom_label_per_product(): void
    {
        $taxonomy = $this->catalogContext->createTaxonomy('taxonomy-aaa', TaxonomyType::property->value);
        $taxon = $this->catalogContext->createTaxon();

        $product = $this->catalogContext->createProduct();
        $variantId = $product->getVariants()[0]->variantId;

        $this->catalogContext->linkVariantToTaxon(
            $product->productId->get(),
            $variantId->get(),
            $taxon->taxonId->get(),
            ['title' => ['nl' => 'Custom Label nl', 'fr' => 'Custom Label fr']]
        );

        $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($variantId);

        /** @var ProductTaxonItem $taxon */
        $taxon = $productDetail->getProductProperties()[0];

        $this->assertEquals('Custom Label nl', $taxon->getLabel());
        $this->assertEquals('Custom Label nl', $taxon->getLabel('nl'));
        $this->assertEquals('Custom Label fr', $taxon->getLabel('fr'));
    }

    public function test_it_can_get_grid_flag(): void
    {
        $taxonomy = $this->catalogContext->createTaxonomy('taxonomy-aaa', TaxonomyType::property->value, [
            'shows_in_grid' => false,
        ]);
        $taxon = $this->catalogContext->createTaxon('taxon-aaa', $taxonomy->taxonomyId->get(), null, [
            'state' => TaxonState::offline->value,
        ]);

        $product = $this->catalogContext->createProduct();
        $variantId = $product->getVariants()[0]->variantId;

        $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());

        $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($variantId);

        /** @var ProductTaxonItem $taxon */
        $taxon = $productDetail->getTaxa()[0];

        $this->assertFalse($taxon->showOnline());
        $this->assertFalse($taxon->showsInGrid());
    }

    public function test_it_can_get_online_taxa()
    {
        // Create a collection taxon
        foreach (['property', 'variant_property', 'category', 'collection', 'tag', 'google_category'] as $taxonomyType) {

            $taxonomy = $this->catalogContext->createTaxonomy('taxonomy-aaa', $taxonomyType);
            $taxon = $this->catalogContext->createTaxon();

            $taxonOffline = $this->catalogContext->createTaxon('taxon-bbb', $taxonomy->taxonomyId->get(), null, [
                'state' => TaxonState::offline->value,
            ]);

            $product = $this->catalogContext->createProduct();
            $variantId = $product->getVariants()[0]->variantId;

            if ($taxonomyType == 'variant_property') {
                $this->catalogContext->linkVariantToTaxon($product->productId->get(), $variantId->get(), $taxon->taxonId->get());
                $this->catalogContext->linkVariantToTaxon($product->productId->get(), $variantId->get(), $taxonOffline->taxonId->get());
            } else {
                $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());
                $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxonOffline->taxonId->get());
            }

            $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($variantId);

            $taxa = $productDetail->getTaxa();

            $this->assertCount(2, $taxa);

            if ($taxonomyType === 'collection') {
                $this->assertCount(1, $productDetail->getCollections());
            } elseif ($taxonomyType === 'tag') {
                $this->assertCount(1, $productDetail->getTags());
            } elseif ($taxonomyType === 'property') {
                $this->assertCount(1, $productDetail->getProductProperties());
            } elseif ($taxonomyType === 'variant_property') {
                $this->assertCount(1, $productDetail->getVariantProperties());
            } elseif ($taxonomyType === 'category') {
                $this->assertCount(1, $productDetail->getCategories());
            } else {
                $this->assertCount(1, $productDetail->getGoogleCategories());
            }
        }
    }

    public function test_it_can_get_keys_and_url_per_taxa_item(): void
    {
        $this->catalogContext->createTaxonomy();
        $taxon = $this->catalogContext->createTaxon();
        $taxon2 = $this->catalogContext->createTaxon('taxon-bbb');

        $product = $this->catalogContext->createProduct();
        $variantId = $product->getVariants()[0]->variantId;

        $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());
        $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxon2->taxonId->get());

        $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($variantId);

        $taxa = $productDetail->getTaxa();

        /** @var ProductTaxonItem $taxon */
        $taxon = $taxa[0];

        $this->assertEquals('taxon-aaa-key-nl', $taxon->getKey());
        $this->assertEquals('taxon-aaa-key-nl', $taxon->getKey('nl'));
        $this->assertEquals('taxon-aaa-key-fr', $taxon->getKey('fr'));
        $this->assertEquals('taxon-aaa-key-nl', $taxon->getKey('de')); // fallback

        $this->assertEquals('taxon-aaa-key-nl', $taxon->getUrl());
        $this->assertEquals('taxon-aaa-key-nl', $taxon->getUrl('nl'));
        $this->assertEquals('taxon-aaa-key-fr', $taxon->getUrl('fr'));
    }
}
