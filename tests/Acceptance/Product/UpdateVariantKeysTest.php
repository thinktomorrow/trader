<?php
declare(strict_types=1);

namespace Acceptance\Product;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\Product\UpdateVariantKeys;

class UpdateVariantKeysTest extends TestCase
{
    public function test_it_can_update_keys(): void
    {
        $product = $this->catalogContext->createProduct();
        $variant = $product->getVariants()[0];

        $this->catalogContext->apps()->productApplication()->updateVariantKeys(new UpdateVariantKeys(
            $product->productId->get(),
            $variant->variantId->get(),
            ['nl' => 'new-key-nl', 'fr' => 'new-key-fr'],
        ));

        $updatedProduct = $this->catalogContext->repos()->productRepository()->find($product->productId);
        $updatedVariant = $updatedProduct->findVariant($variant->variantId);

        $this->assertCount(2, $updatedVariant->getVariantKeys());
        $this->assertEquals('new-key-nl', $updatedVariant->getVariantKeys()[0]->getKey());
        $this->assertEquals('new-key-fr', $updatedVariant->getVariantKeys()[1]->getKey());
    }

    public function test_key_is_unique_per_locale(): void
    {
        $product = $this->catalogContext->createProduct();
        $variant = $product->getVariants()[0];

        $this->catalogContext->apps()->productApplication()->updateVariantKeys(new UpdateVariantKeys(
            $product->productId->get(),
            $variant->variantId->get(),
            ['nl' => 'new-key-xxx', 'fr' => 'new-key-xxx'],
        ));

        $updatedProduct = $this->catalogContext->repos()->productRepository()->find($product->productId);
        $updatedVariant = $updatedProduct->findVariant($variant->variantId);
        
        $this->assertCount(2, $updatedVariant->getVariantKeys());
        $this->assertEquals('new-key-xxx', $updatedVariant->getVariantKeys()[0]->getKey());
        $this->assertEquals('new-key-xxx', $updatedVariant->getVariantKeys()[1]->getKey());
    }
}
