<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantProperty;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantPropertyRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
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
        $this->catalogContext->repos()->productRepository() = new MysqlProductRepository(new MysqlVariantRepository(new TestContainer()));
    }

    public function test_it_can_check_if_variant_property_combination_exists()
    {
        $this->createAndSaveTaxonomiesAndTaxa();
        $product = $this->createProductWithProductVariantProperties();
        $this->catalogContext->repos()->productRepository()->save($product);

        $variant = $product->getVariants()[0];

        foreach ($this->repositories() as $repository) {

            // Check if combo already exists
            $taxonIds = array_map(fn($variantTaxonState) => $variantTaxonState['taxon_id'], $variant->getChildEntities()[VariantTaxon::class]);

            $this->assertTrue($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), $taxonIds));

            // Passed variant is ignored in the check
            $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), $taxonIds, $variant->variantId->get()));

            $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), ['aaa']));
            $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), ['aaa', 'bbb']));
        }
    }

    public function test_it_checks_various_variant_property_combinations()
    {
        $this->createAndSaveTaxonomiesAndTaxa();
        $product = $this->createProductWithProductVariantProperties();
        $this->catalogContext->repos()->productRepository()->save($product);

        $repository = new MysqlVariantPropertyRepository();

        $variants = $product->getVariants();
        $variantA = $variants[0];

        $taxonIdsA = array_map(fn($variantTaxonState) => $variantTaxonState['taxon_id'], $variantA->getChildEntities()[VariantTaxon::class]);

        // Case 1: exists but exclude self => false
        //        $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), $taxonIdsA, $variantA->variantId->get()));

        // Maak een tweede variant met exact dezelfde set
        $product->createVariant($this->createVariantWithVariantProperty('zzz'));
        $this->catalogContext->repos()->productRepository()->save($product);

        // Case 2: duplicate already exists
        $this->assertTrue($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), $taxonIdsA));

        // Case 3: exact duplicate (andere variant) => true
        $this->assertTrue($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), $taxonIdsA, $variantA->variantId->get()));

        // Case 3: superset => false
        $extra = array_merge($taxonIdsA, ['yyy']);
        $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), $extra));

        // Case 4: completely different => false
        $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), ['zzz', 'yyy']));

        // Case 5: empty array => false
        $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), []));

        // Case 6: multiple overlapping variants
        $variantB = $this->createVariant('aaa');
        $variantB->updateVariantProperties([
            VariantProperty::create($variantB->variantId, TaxonId::fromString('yyy')),
        ]);
        $product->createVariant($variantB);
        $this->catalogContext->repos()->productRepository()->save($product);

        $this->assertTrue($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), ['yyy']));
        $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), ['yyy'], $variantB->variantId->get()));
        $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist($product->productId->get(), [$taxonIdsA[0], 'yyy']));
    }

    private static function repositories(): \Generator
    {
        yield new MysqlVariantPropertyRepository();
    }
}
