<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\UpdateVariant\UpdateVariantOptionValues;

class UpdateVariantOptionsTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_update_option_values()
    {
        $product = $this->createProductWithOptions();
        $this->productRepository->save($product);

        $variant = $product->getVariants()[0];

        $this->assertEquals(['xxx', 'zzz'], $variant->getMappedData()['option_value_ids']);

        $this->productApplication->updateVariantOptionValues(new UpdateVariantOptionValues(
            $product->productId->get(),
            $variant->variantId->get(),
            ['xxx']
        ));

        $product = $this->productRepository->find($product->productId);
        $variant = $product->getVariants()[0];

        $this->assertEquals(['xxx'], $variant->getMappedData()['option_value_ids']);
    }
}
