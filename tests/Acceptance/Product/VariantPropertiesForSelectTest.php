<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\VariantProperties\VariantPropertiesForSelect;

class VariantPropertiesForSelectTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_compose_a_simple_option_array_for_select_field_rendering()
    {
        $this->createAndSaveTaxonomiesAndTaxa();
        $product = $this->createProductWithProductVariantProperties();
        $this->catalogContext->catalogRepos()->productRepository()->save($product);

        $values = (new VariantPropertiesForSelect($this->catalogContext->catalogRepos()->productRepository(), $this->taxonRepository, $this->taxonomyRepository))->get(
            $product->productId->get(),
        );

        $this->assertEquals([
            'qqq' => [
                'label' => 'Taxonomy qqq nl',
                'options' => [
                    ['value' => 'xxx', 'label' => 'Taxon xxx nl'],
                    ['value' => 'yyy', 'label' => 'Taxon yyy nl'],
                    ['value' => 'zzz', 'label' => 'Taxon zzz nl'],
                ],
            ],
        ], $values);
    }
}
