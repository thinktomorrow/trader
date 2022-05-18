<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\CheckProductOptions\MissingOptionCombinations;

class MissingOptionCombinationsTest extends ProductContext
{
    use TestHelpers;

    /** @test */
    public function it_can_check_missing_combos()
    {
        $product = $this->createdProductWithOptions();
        $this->productRepository->save($product);

        $missingCombos = $this->missingOptionCombinations->get($product);

        $this->assertCount(1, $missingCombos);
        $this->assertCount(2, $missingCombos[0]);
        $this->assertEquals(['yyy','zzz'], $missingCombos[0]);
    }

    /** @test */
    public function it_can_render_missing_combos_with_labels()
    {
        $product = $this->createdProductWithOptions();
        $this->productRepository->save($product);

        $missingComboLabels = $this->missingOptionCombinations->getAsLabels($product, 'foo', 'value.nl');

        $this->assertCount(1, $missingComboLabels);
        $this->assertEquals([
            'bar: option value nl 2',
            'baz: option value nl 3'
        ], $missingComboLabels[0]);
    }
}
