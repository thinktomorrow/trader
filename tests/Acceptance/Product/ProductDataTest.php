<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Common\Locale;

class ProductDataTest extends ProductContext
{
    use TestHelpers;

    /** @test */
    public function it_can_render_localized_data()
    {
        $product = $this->createProductWithOptions();
        $product->addData([
            'content' => ['nl' => 'content nl', 'en' => 'content en'],
        ]);
        $product->getVariants()[0]->addData([
            'title' => ['nl' => 'title nl', 'en' => 'title en'],
        ]);
        $this->productRepository->save($product);

        $productDetail = $this->productDetailRepository->findProductDetail($product->getVariants()[0]->variantId);

        // Default test locale is nl
        $this->assertEquals('title nl', $productDetail->getTitle());
        $this->assertEquals('content nl', $productDetail->getIntro());
        $this->assertEquals('content nl', $productDetail->getContent());

        $productDetail->setLocale(Locale::make('en', 'BE'));
        $this->assertEquals('title en', $productDetail->getTitle());
        $this->assertEquals('content en', $productDetail->getIntro());
        $this->assertEquals('content en', $productDetail->getContent());
    }
}
