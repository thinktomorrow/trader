<?php

declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Common\Locale;

class ProductDataTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_render_localized_data()
    {
        $product = $this->catalogContext->createProduct('product-aaa', null, [], [
            'content' => ['nl' => 'content nl', 'en' => 'content en'],
        ]);
        $variant = $this->catalogContext->createVariant($product->productId->get(), 'variant-aaa', [], [
            'title' => ['nl' => 'title nl', 'en' => 'title en'],
        ]);

        $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($product->getVariants()[0]->variantId);

        // Default test locale is nl
        $this->assertEquals('title nl', $productDetail->getTitle());
        $this->assertEquals('content nl', $productDetail->getIntro());
        $this->assertEquals('content nl', $productDetail->getContent());

        $productDetail->setLocale(Locale::fromString('en', 'BE'));
        $this->assertEquals('title en', $productDetail->getTitle());
        $this->assertEquals('content en', $productDetail->getIntro());
        $this->assertEquals('content en', $productDetail->getContent());
    }
}
