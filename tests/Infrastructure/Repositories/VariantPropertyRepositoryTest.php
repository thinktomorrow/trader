<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantPropertyRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

final class VariantPropertyRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected MysqlTaxonRepository $taxonRepository;
    protected MysqlTaxonomyRepository $taxonomyRepository;
    protected MysqlProductRepository $productRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->taxonRepository = new MysqlTaxonRepository();
        $this->taxonomyRepository = new MysqlTaxonomyRepository(new TestContainer());
    }

    public function test_it_can_check_if_variant_property_combination_exists()
    {
        $this->catalogContext->createTaxonomy('taxonomy-aaa', TaxonomyType::variant_property->value);
        $taxonomy2 = $this->catalogContext->createTaxonomy('taxonomy-bbb', TaxonomyType::variant_property->value);
        $taxon = $this->catalogContext->createTaxon();
        $taxon2 = $this->catalogContext->createTaxon('taxon-bbb');
        $taxon3 = $this->catalogContext->createTaxon('taxon-ccc', $taxonomy2->taxonomyId->get());

        $product = $this->catalogContext->createProduct();
        $variantId = $product->getVariants()[0]->variantId;

        $this->catalogContext->linkVariantToTaxon($product->productId->get(), $variantId->get(), $taxon->taxonId->get());
        $this->catalogContext->linkVariantToTaxon($product->productId->get(), $variantId->get(), $taxon3->taxonId->get());

        $product = $this->catalogContext->findProduct($product->productId);
        $variant = $product->getVariants()[0];

        foreach ($this->repositories() as $repository) {

            // Check if combo already exists
            $taxonIds = array_map(fn($variantTaxonState) => $variantTaxonState['taxon_id'], $variant->getChildEntities()[VariantTaxon::class]);

            $this->assertTrue($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), $taxonIds));

            // Passed variant is ignored in the check
            $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), $taxonIds, $variant->variantId->get()));

            $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), ['taxon-aaa']));
            $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), ['taxon-bbb', 'taxon-ccc']));
        }
    }

    public function test_it_checks_various_variant_property_combinations()
    {
        $this->catalogContext->createTaxonomy('taxonomy-aaa', TaxonomyType::variant_property->value);
        $taxonomy2 = $this->catalogContext->createTaxonomy('taxonomy-bbb', TaxonomyType::variant_property->value);
        $taxon = $this->catalogContext->createTaxon('taxon-aaa');
        $taxon2 = $this->catalogContext->createTaxon('taxon-bbb');
        $taxon3 = $this->catalogContext->createTaxon('taxon-ccc', $taxonomy2->taxonomyId->get());

        $product = $this->catalogContext->createProduct();
        $variantId = $product->getVariants()[0]->variantId;

        $this->catalogContext->linkVariantToTaxon($product->productId->get(), $variantId->get(), $taxon->taxonId->get());
        $this->catalogContext->linkVariantToTaxon($product->productId->get(), $variantId->get(), $taxon3->taxonId->get());

        $product = $this->catalogContext->findProduct($product->productId);
        $repository = new MysqlVariantPropertyRepository();
        $variants = $product->getVariants();
        $variantA = $variants[0];

        $taxonIdsA = array_map(fn($variantTaxonState) => $variantTaxonState['taxon_id'], $variantA->getChildEntities()[VariantTaxon::class]);

        // Case 1: exists but exclude self => false
        //        $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), $taxonIdsA, $variantA->variantId->get()));

        // Maak een tweede variant met exact dezelfde set
        $variantB = $this->catalogContext->createVariant($product->productId->get(), 'variant-bbb');
        $this->catalogContext->linkVariantToTaxon($product->productId->get(), $variantB->variantId->get(), $taxon->taxonId->get());
        $this->catalogContext->linkVariantToTaxon($product->productId->get(), $variantB->variantId->get(), $taxon3->taxonId->get());

        // Case 2: duplicate already exists
        $this->assertTrue($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), $taxonIdsA));

        // Case 3: exact duplicate (andere variant) => true
        $this->assertTrue($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), $taxonIdsA, $variantA->variantId->get()));

        // Case 3: superset => false
        $extra = array_merge($taxonIdsA, ['taxon-extra']);
        $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), $extra));

        // Case 4: completely different => false
        $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), ['zzz', 'taxon-aaa']));

        // Case 5: empty array => false
        $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), []));

        // Case 6: multiple overlapping variants
        $variantC = $this->catalogContext->createVariant($product->productId->get(), 'variant-ccc');
        $this->catalogContext->linkVariantToTaxon(
            $product->productId->get(),
            $variantC->variantId->get(),
            'taxon-aaa'
        );

        $this->assertTrue(
            $repository->doesUniqueVariantPropertyCombinationExist(
                $product->productId->get(),
                ['taxon-aaa']
            )
        );

        // exclude self => false
        $this->assertFalse(
            $repository->doesUniqueVariantPropertyCombinationExist(
                $product->productId->get(),
                ['taxon-aaa'],
                $variantC->variantId->get()
            )
        );

        // partial overlap is NOT exact match
        $this->assertFalse(
            $repository->doesUniqueVariantPropertyCombinationExist(
                $product->productId->get(),
                ['taxon-aaa', 'taxon-bbb']
            )
        );
    }

    private static function repositories(): \Generator
    {
        yield new MysqlVariantPropertyRepository();
    }
}
