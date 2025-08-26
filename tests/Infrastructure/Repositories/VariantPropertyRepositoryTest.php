<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantTaxon;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantPropertyRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

final class VariantPropertyRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_check_if_variant_property_combination_exists()
    {
        $product = $this->createProductWithProductVariantProperties();
        (new MysqlProductRepository(new MysqlVariantRepository(new TestContainer())))->save($product);

        $variant = $product->getVariants()[0];

        foreach ($this->repositories() as $repository) {

            // Check if combo already exists
            $taxonIds = array_map(fn ($variantTaxonState) => $variantTaxonState['taxon_id'], $variant->getChildEntities()[VariantTaxon::class]);

            $this->assertTrue($repository->doesUniqueVariantPropertyCombinationExist($taxonIds));

            // Passed variant is ignored in the check
            $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist($taxonIds, $variant->variantId->get()));

            $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist(['aaa']));
            $this->assertFalse($repository->doesUniqueVariantPropertyCombinationExist(['aaa', 'bbb']));
        }
    }

    private static function repositories(): \Generator
    {
        yield new MysqlVariantPropertyRepository();
    }
}
