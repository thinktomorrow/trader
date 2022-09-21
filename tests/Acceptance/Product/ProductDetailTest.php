<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;

class ProductDetailTest extends ProductContext
{
    use TestHelpers;

    /** @test */
    public function it_can_get_a_product_detail()
    {
        $product = $this->createdProductWithOptions();
        $product->updateTaxonIds([
            TaxonId::fromString('1'),
            TaxonId::fromString('2'),
        ]);
        $product->addData([
            'title' => [
                'nl' => 'product title nl',
                'en' => 'product title en',
            ],
        ]);

        $product->getVariants()[0]->addData([
            'title' => [
                'nl' => 'variant title nl',
                'en' => 'variant title en',
            ],
        ]);

        $this->productRepository->save($product);

        $productDetail = $this->productDetailRepository->findProductDetail($product->getVariants()[0]->variantId);

        $this->assertEquals($product->getVariants()[0]->variantId->get(), $productDetail->getVariantId());
        $this->assertEquals($product->getVariants()[0]->productId->get(), $productDetail->getProductId());
        $this->assertTrue($productDetail->isAvailable());
        $this->assertEquals('€ 0,12', $productDetail->getUnitPrice(true));
        $this->assertEquals('€ 0,10', $productDetail->getUnitPrice(false));
        $this->assertEquals('€ 0,10', $productDetail->getSalePrice(true));
        $this->assertEquals('€ 0,08', $productDetail->getSalePrice(false));
        $this->assertEquals('variant title nl', $productDetail->getTitle());
        $this->assertEquals(['1','2'], $productDetail->getTaxonIds());
    }

    /** @test */
    public function if_variant_title_is_empty_it_uses_product_title()
    {
        $product = $this->createdProductWithOptions();
        $product->addData([
            'title' => [
                'nl' => 'product title nl',
                'en' => 'product title en',
            ],
        ]);

        $this->productRepository->save($product);

        $productDetail = $this->productDetailRepository->findProductDetail($product->getVariants()[0]->variantId);
        $this->assertEquals('product title nl', $productDetail->getTitle());
    }

    /** @test */
    public function if_variant_title_is_empty_it_uses_product_title_and_option_title()
    {
        $product = $this->createdProductWithOptions();
        $product->addData([
            'title' => [
                'nl' => 'product title nl',
                'en' => 'product title en',
            ],
        ]);

        $product->getVariants()[0]->addData([
            'option_title' => [
                'nl' => 'variant option_title nl',
                'en' => 'variant option_title en',
            ],
        ]);

        $this->productRepository->save($product);

        $this->productRepository->save($product);

        $productDetail = $this->productDetailRepository->findProductDetail($product->getVariants()[0]->variantId);
        $this->assertEquals('product title nl variant option_title nl', $productDetail->getTitle());
    }

    /** @test */
    public function it_can_add_images()
    {
        $product = $this->createdProductWithOptions();
        $this->productRepository->save($product);

        $productDetail = $this->productDetailRepository->findProductDetail($product->getVariants()[0]->variantId);
        $productDetail->setImages(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $productDetail->getImages());
    }
}
