<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductTaxonRepository;

class MissingVariantPropertyCombinationsTest extends ProductContext
{
    use TestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        InMemoryProductTaxonRepository::$productTaxonLookup = [

        ];
    }

    public function tearDown(): void
    {
        InMemoryProductTaxonRepository::clear();

        parent::tearDown();
    }

    public function test_it_can_check_missing_combos()
    {
        $product = $this->createProductWithProductVariantProperties();
        $this->productRepository->save($product);

        $missingCombos = $this->missingOptionCombinations->get($product);

        $this->assertCount(1, $missingCombos);
        $this->assertCount(2, $missingCombos[0]);
        $this->assertEquals(['yyy', 'zzz'], $missingCombos[0]);
    }

    public function test_it_can_render_missing_combos_with_labels()
    {
        $product = $this->createProductWithProductVariantProperties();
        $this->productRepository->save($product);

        $missingComboLabels = $this->missingOptionCombinations->getAsLabels($product, 'foo', 'value.nl');

        $this->assertCount(1, $missingComboLabels);
        $this->assertEquals([
            'bar: option value nl 2',
            'baz: option value nl 3',
        ], $missingComboLabels[0]);
    }
}
