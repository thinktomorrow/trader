<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\VariantProperties\VariantPropertiesForSelect;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class VariantPropertiesForSelectTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_compose_a_simple_option_array_for_select_field_rendering()
    {
        $this->catalogContext->createTaxonomy('taxonomy-aaa', TaxonomyType::variant_property->value);
        $taxon = $this->catalogContext->createTaxon();
        $taxon2 = $this->catalogContext->createTaxon('taxon-bbb');

        $product = $this->catalogContext->createProduct();
        $variantId = $product->getVariants()[0]->variantId;

        $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());
        $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxon2->taxonId->get());
//        $this->catalogContext->linkVariantToTaxon($product->productId->get(), $variantId->get(), $taxon->taxonId->get());
//        $this->catalogContext->linkVariantToTaxon($product->productId->get(), $variantId->get(), $taxon2->taxonId->get());

        $values = (new VariantPropertiesForSelect(
            $this->catalogContext->repos()->productRepository(),
            $this->catalogContext->repos()->taxonRepository(),
            $this->catalogContext->repos()->taxonomyRepository(),
        ))->get(
            $product->productId->get(),
        );

        $this->assertEquals([
            'taxonomy-aaa' => [
                'label' => 'taxonomy-aaa title nl',
                'options' => [
                    ['value' => 'taxon-aaa', 'label' => 'taxon-aaa title nl'],
                    ['value' => 'taxon-bbb', 'label' => 'taxon-bbb title nl'],
                ],
            ],
        ], $values);
    }
}
