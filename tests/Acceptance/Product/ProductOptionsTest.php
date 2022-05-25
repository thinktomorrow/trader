<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValue;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;

class ProductOptionsTest extends ProductContext
{
    use TestHelpers;

    /** @test */
    public function it_can_create_a_product_option()
    {
        $productOption = OptionValue::create(OptionId::fromString('aaa'), OptionValueId::fromString('aaa-value'), []);

        $this->assertEquals(OptionId::fromString('aaa'), $productOption->optionId);
        $this->assertEquals(OptionValueId::fromString('aaa-value'), $productOption->optionValueId);
    }

    /** @test */
    public function it_can_compose_options()
    {
        $product = $this->createdProductWithOptions();
        $this->productRepository->save($product);

        $productOptions = $this->productOptionsComposer->get(
            $product->productId,
            $product->getVariants()[0]->variantId,
            Locale::fromString('nl','be')
        );

        $this->assertCount(3, $productOptions);
        $this->assertNotNull($productOptions[0]->getUrl());
        $this->assertNotNull($productOptions[2]->getUrl());
        $this->assertNull($productOptions[1]->getUrl()); // Second one has no variant to point at so no url
    }
}
