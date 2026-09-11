<?php

declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\VariantLinks\VariantLinks;
use Thinktomorrow\Trader\Domain\Common\Locale;

class VariantLinksTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_compose_links_from_a_loaded_product_without_reloading_it(): void
    {
        $product = $this->catalogContext->createProduct();
        $this->catalogContext->createVariant($product->productId->get(), 'variant-bbb');
        $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($product->getVariants()[0]->variantId);
        $composer = $this->catalogContext->repos()->variantLinksComposer();
        $locale = Locale::fromString('fr');
        $expected = $composer->get($productDetail, $locale);
        $this->catalogContext->repos()->productRepository()->delete($product->productId);

        $links = $composer->getForProduct($product, $productDetail, $locale);

        $this->assertCount(2, $links);
        $this->assertEquals($expected, $links);
        $this->assertSame('variant-aaa option title fr', $links[0]->getLabel());
        $this->assertSame('/variant-bbb', $links[1]->getUrl());
    }

    public function test_it_can_compose_variant_links()
    {
        $product = $this->catalogContext->createProduct();
        $variant1 = $this->catalogContext->createVariant($product->productId->get(), 'variant-bbb');
        $variant2 = $this->catalogContext->createVariant($product->productId->get(), 'variant-ccc');

        $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($product->getVariants()[0]->variantId);

        $links = $this->catalogContext->repos()->variantLinksComposer()->get(
            $productDetail,
            Locale::fromString('nl')
        );

        $this->assertCount(3, $links);
        $this->assertInstanceOf(VariantLinks::class, $links);
    }

    public function test_it_can_compose_variant_links_url_and_label()
    {
        $product = $this->catalogContext->createProduct();
        $variant1 = $this->catalogContext->createVariant($product->productId->get(), 'variant-bbb');
        $variant2 = $this->catalogContext->createVariant($product->productId->get(), 'variant-ccc');

        $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($product->getVariants()[0]->variantId);

        $links = $this->catalogContext->repos()->variantLinksComposer()->get(
            $productDetail,
            Locale::fromString('nl')
        );

        $this->assertEquals('/variant-aaa', $links[0]->getUrl());
        $this->assertEquals('/variant-bbb', $links[1]->getUrl());
        $this->assertEquals('/variant-ccc', $links[2]->getUrl());

        $this->assertEquals('variant-aaa option title nl', $links[0]->getLabel());
        $this->assertEquals('variant-bbb option title nl', $links[1]->getLabel());
        $this->assertEquals('variant-ccc option title nl', $links[2]->getLabel());
    }

    public function test_it_can_compose_variant_links_label_per_locale()
    {
        $product = $this->catalogContext->createProduct();

        $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($product->getVariants()[0]->variantId);

        $links = $this->catalogContext->repos()->variantLinksComposer()->get(
            $productDetail,
            Locale::fromString('fr')
        );

        $this->assertEquals('variant-aaa option title fr', $links[0]->getLabel());
    }
}
