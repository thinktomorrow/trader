<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class ProductTaxaTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_get_a_product_taxon_item()
    {
        [$taxonomies, $taxa] = $this->createTaxonomiesAndTaxa();

        foreach ($taxonomies as $taxonomy) {
            $this->taxonomyRepository->save($taxonomy);
        }

        foreach ($taxa as $taxon) {
            $this->taxonRepository->save($taxon);
        }

        $product = $this->createProductWithProductVariantProperties();
        $this->productRepository->save($product);

        $variantId = $product->getVariants()[0]->variantId;
        $productDetail = $this->productDetailRepository->findProductDetail($variantId);

        $taxa = $productDetail->getTaxa();

        $this->assertCount(5, $taxa);
        $this->assertCount(5, $productDetail->getVariantProperties());
        $this->assertCount(0, $productDetail->getProductProperties());
        $this->assertCount(0, $productDetail->getCategories());
        $this->assertCount(0, $productDetail->getGoogleCategories());
        $this->assertCount(0, $productDetail->getTags());
        $this->assertCount(0, $productDetail->getCollections());
    }

    public function test_it_can_get_details_of_taxon_item(): void
    {
        // Create a category taxonomy and taxon
        $taxonomyId = TaxonomyId::fromString('ooo');
        $taxonId = TaxonId::fromString('xxx');

        $taxonomy = \Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy::create($taxonomyId, TaxonomyType::property);
        $taxon = \Thinktomorrow\Trader\Domain\Model\Taxon\Taxon::create($taxonId, $taxonomyId);
        $this->taxonomyRepository->save($taxonomy);
        $this->taxonRepository->save($taxon);

        // Create a product and assign the product property taxon
        $product = static::createProductWithVariant();
        $product->updateProductTaxa([
            ProductTaxon::create($product->productId, $taxonId),
        ]);

        $this->productRepository->save($product);

        $variantId = $product->getVariants()[0]->variantId;
        $productDetail = $this->productDetailRepository->findProductDetail($variantId);

        /** @var ProductTaxonItem $taxon */
        $taxon = $productDetail->getProductProperties()[0];

        $this->assertEquals($product->productId->get(), $taxon->getProductId());
        $this->assertEquals($taxonId->get(), $taxon->getTaxonId());
        $this->assertEquals($taxonomyId->get(), $taxon->getTaxonomyId());
        $this->assertEquals(TaxonomyType::property->value, $taxon->getTaxonomyType());
        $this->assertEquals('', $taxon->getLabel());
        $this->assertFalse($taxon->showsInGrid());
        $this->assertTrue($taxon->showOnline());
    }

    // Test label, override label with custom relation title, showsInGrid, showOnline
    public function test_it_can_get_label(): void
    {
        // Create a category taxonomy and taxon
        $taxonomyId = TaxonomyId::fromString('ooo');
        $taxonId = TaxonId::fromString('xxx');

        $taxonomy = \Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy::create($taxonomyId, TaxonomyType::property);
        $taxon = \Thinktomorrow\Trader\Domain\Model\Taxon\Taxon::create($taxonId, $taxonomyId);
        $taxon->addData(['title' => ['nl' => 'Test Label nl', 'en' => 'Test Label en']]);
        $this->taxonomyRepository->save($taxonomy);
        $this->taxonRepository->save($taxon);

        // Create a product and assign the product property taxon
        $product = static::createProductWithVariant();
        $product->updateProductTaxa([
            ProductTaxon::create($product->productId, $taxonId),
        ]);

        $this->productRepository->save($product);

        $variantId = $product->getVariants()[0]->variantId;
        $productDetail = $this->productDetailRepository->findProductDetail($variantId);

        /** @var ProductTaxonItem $taxon */
        $taxon = $productDetail->getProductProperties()[0];

        $this->assertEquals('Test Label nl', $taxon->getLabel());
        $this->assertEquals('Test Label nl', $taxon->getLabel('nl'));
        $this->assertEquals('Test Label en', $taxon->getLabel('en'));
    }

    public function test_it_can_get_custom_label_per_product(): void
    {
        // Create a category taxonomy and taxon
        $taxonomyId = TaxonomyId::fromString('ooo');
        $taxonId = TaxonId::fromString('xxx');

        $taxonomy = \Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy::create($taxonomyId, TaxonomyType::property);
        $taxon = \Thinktomorrow\Trader\Domain\Model\Taxon\Taxon::create($taxonId, $taxonomyId);
        $taxon->addData(['title' => ['nl' => 'Test Label nl', 'en' => 'Test Label en']]);
        $this->taxonomyRepository->save($taxonomy);
        $this->taxonRepository->save($taxon);

        // Create a product and assign the product property taxon
        $product = static::createProductWithVariant();

        $productTaxon = ProductTaxon::create($product->productId, $taxonId);
        $productTaxon->addData(['title' => ['nl' => 'Custom Label nl', 'en' => 'Custom Label en']]);

        $product->updateProductTaxa([$productTaxon]);

        $this->productRepository->save($product);

        $variantId = $product->getVariants()[0]->variantId;
        $productDetail = $this->productDetailRepository->findProductDetail($variantId);

        /** @var ProductTaxonItem $taxon */
        $taxon = $productDetail->getProductProperties()[0];

        $this->assertEquals('Custom Label nl', $taxon->getLabel());
        $this->assertEquals('Custom Label nl', $taxon->getLabel('nl'));
        $this->assertEquals('Custom Label en', $taxon->getLabel('en'));
    }

    public function test_it_can_get_show_online_state(): void
    {
        // Create a category taxonomy and taxon
        $taxonomyId = TaxonomyId::fromString('ooo');
        $taxonId = TaxonId::fromString('xxx');

        $taxonomy = \Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy::create($taxonomyId, TaxonomyType::property);
        $taxon = \Thinktomorrow\Trader\Domain\Model\Taxon\Taxon::create($taxonId, $taxonomyId);
        $taxon->changeState(TaxonState::online);
        $this->taxonomyRepository->save($taxonomy);
        $this->taxonRepository->save($taxon);

        // Create a product and assign the product property taxon
        $product = static::createProductWithVariant();

        $productTaxon = ProductTaxon::create($product->productId, $taxonId);
        $product->updateProductTaxa([$productTaxon]);
        $this->productRepository->save($product);

        $variantId = $product->getVariants()[0]->variantId;
        $productDetail = $this->productDetailRepository->findProductDetail($variantId);

        /** @var ProductTaxonItem $taxon */
        $taxon = $productDetail->getProductProperties()[0];

        $this->assertTrue($taxon->showOnline());
    }

    public function test_it_can_get_show_in_grid_flag(): void
    {
        // Create a category taxonomy and taxon
        $taxonomyId = TaxonomyId::fromString('ooo');
        $taxonId = TaxonId::fromString('xxx');

        $taxonomy = \Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy::create($taxonomyId, TaxonomyType::property);
        $taxon = \Thinktomorrow\Trader\Domain\Model\Taxon\Taxon::create($taxonId, $taxonomyId);
        $taxonomy->showInGrid();
        $this->taxonomyRepository->save($taxonomy);
        $this->taxonRepository->save($taxon);

        // Create a product and assign the product property taxon
        $product = static::createProductWithVariant();

        $productTaxon = ProductTaxon::create($product->productId, $taxonId);
        $product->updateProductTaxa([$productTaxon]);
        $this->productRepository->save($product);

        $variantId = $product->getVariants()[0]->variantId;
        $productDetail = $this->productDetailRepository->findProductDetail($variantId);

        /** @var ProductTaxonItem $taxon */
        $taxon = $productDetail->getProductProperties()[0];

        $this->assertTrue($taxon->showsInGrid());
    }

    public function test_it_can_get_specific_taxa_that_are_online()
    {
        // Create a collection taxon
        foreach (['property', 'variant_property', 'category', 'collection', 'tag', 'google_category'] as $taxonomyType) {
            $taxonomyId = TaxonomyId::fromString('ooo_' . $taxonomyType);
            $taxonId = TaxonId::fromString('xxx_' . $taxonomyType);
            $taxonOfflineId = TaxonId::fromString('yyy_' . $taxonomyType);
            $taxonomy = \Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy::create($taxonomyId, TaxonomyType::from($taxonomyType));
            $taxon = \Thinktomorrow\Trader\Domain\Model\Taxon\Taxon::create($taxonId, $taxonomyId);
            $taxon->changeState(TaxonState::online);
            $taxonOffline = \Thinktomorrow\Trader\Domain\Model\Taxon\Taxon::create($taxonOfflineId, $taxonomyId);
            $taxonOffline->changeState(TaxonState::offline);
            $this->taxonomyRepository->save($taxonomy);
            $this->taxonRepository->save($taxon);
            $this->taxonRepository->save($taxonOffline);

            // Create a product and assign the taxon
            $product = static::createProductWithVariant();
            $product->updateProductTaxa([
                ProductTaxon::create($product->productId, $taxonId),
                ProductTaxon::create($product->productId, $taxonOfflineId),
            ]);

            $this->productRepository->save($product);

            $variantId = $product->getVariants()[0]->variantId;
            $productDetail = $this->productDetailRepository->findProductDetail($variantId);

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
}
