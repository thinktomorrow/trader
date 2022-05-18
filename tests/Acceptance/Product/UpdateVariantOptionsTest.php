<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Event\OptionsUpdated;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductOptions;
use Thinktomorrow\Trader\Application\Product\UpdateVariant\UpdateVariantOptionValues;

class UpdateVariantOptionsTest extends ProductContext
{
    use TestHelpers;

    /** @test */
    public function it_can_update_option_values()
    {
        $product = $this->createdProductWithOptions();
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
