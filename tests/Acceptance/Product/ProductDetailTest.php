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
        $product = $this->createProductWithOptions();
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

        $variantId = $product->getVariants()[0]->variantId;

        $productDetail = $this->productDetailRepository->findProductDetail($variantId);

        $this->assertEquals($variantId->get(), $productDetail->getVariantId());
        $this->assertEquals($product->getVariants()[0]->productId->get(), $productDetail->getProductId());
        $this->assertTrue($productDetail->isAvailable());
        $this->assertEquals('€ 0,12', $productDetail->getUnitPrice(true));
        $this->assertEquals('€ 0,10', $productDetail->getUnitPrice(false));
        $this->assertEquals('€ 0,10', $productDetail->getSalePrice(true));
        $this->assertEquals('€ 0,08', $productDetail->getSalePrice(false));
        $this->assertEquals('variant title nl', $productDetail->getTitle());
        $this->assertEquals(['1','2'], $productDetail->getTaxonIds());
    }

    public function test_it_can_get_sku_and_ean()
    {
        $product = $this->createProductWithVariant();
        $this->productRepository->save($product);

        $variantId = $product->getVariants()[0]->variantId;
        $productDetail = $this->productDetailRepository->findProductDetail($variantId);

        // Fallback values for sku / ean
        $this->assertEquals('fake-sku', $productDetail->getSku());
        $this->assertNull($productDetail->getEan());

        // Set specific sku / ean values
        $product->getVariants()[0]->updateSku('sku-foobar');
        $product->getVariants()[0]->updateEan('ean-foobar');
        $this->productRepository->save($product);

        $productDetail = $this->productDetailRepository->findProductDetail($variantId);
        $this->assertEquals('sku-foobar', $productDetail->getSku());
        $this->assertEquals('ean-foobar', $productDetail->getEan());
    }

    /** @test */
    public function if_variant_title_is_empty_it_uses_product_title()
    {
        $product = $this->createProductWithOptions();
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
        $product = $this->createProductWithOptions();
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
        $product = $this->createProductWithOptions();
        $this->productRepository->save($product);

        $productDetail = $this->productDetailRepository->findProductDetail($product->getVariants()[0]->variantId);
        $productDetail->setImages(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $productDetail->getImages());
    }
}
