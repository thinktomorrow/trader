<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;

class ProductDetailTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_get_a_product_detail()
    {
        $this->catalogContext->createTaxonomy();
        $taxon = $this->catalogContext->createTaxon();
        $taxon2 = $this->catalogContext->createTaxon('taxon-bbb');

        $product = $this->catalogContext->createProduct();
        $variantId = $product->getVariants()[0]->variantId;

        $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());
        $this->catalogContext->linkProductToTaxon($product->productId->get(), $taxon2->taxonId->get());

        $productDetail = $this->catalogContext->catalogRepos()->productDetailRepository()->findProductDetail($variantId);

        $this->assertEquals($variantId->get(), $productDetail->getVariantId());
        $this->assertEquals($product->getVariants()[0]->productId->get(), $productDetail->getProductId());
        $this->assertTrue($productDetail->isAvailable());
        $this->assertEquals('€ 1,20', $productDetail->getUnitPrice(true));
        $this->assertEquals('€ 1', $productDetail->getUnitPrice(false));
        $this->assertEquals('€ 0,96', $productDetail->getSalePrice(true));
        $this->assertEquals('€ 0,80', $productDetail->getSalePrice(false));
        $this->assertEquals('variant-aaa title nl', $productDetail->getTitle());

        $this->assertCount(2, $productDetail->getTaxa());
        $this->assertContainsOnlyInstancesOf(ProductTaxonItem::class, $productDetail->getTaxa());
    }

    public function test_it_can_get_sku_and_ean()
    {
        $product = $this->catalogContext->createProduct();
        $this->catalogContext->catalogRepos()->productRepository()->save($product);

        $variantId = $product->getVariants()[0]->variantId;
        $productDetail = $this->catalogContext->catalogRepos()->productDetailRepository()->findProductDetail($variantId);

        // Fallback values for sku / ean
        $this->assertEquals('sku-variant-aaa', $productDetail->getSku());
        $this->assertNull($productDetail->getEan());

        // Set specific sku / ean values
        $product->getVariants()[0]->updateSku('sku-foobar');
        $product->getVariants()[0]->updateEan('ean-foobar');
        $this->catalogContext->catalogRepos()->productRepository()->save($product);

        $productDetail = $this->catalogContext->catalogRepos()->productDetailRepository()->findProductDetail($variantId);
        $this->assertEquals('sku-foobar', $productDetail->getSku());
        $this->assertEquals('ean-foobar', $productDetail->getEan());
    }

    public function test_if_variant_title_is_empty_it_uses_product_title()
    {
        $product = $this->catalogContext->createProduct('product-aaa', null, [], [
            'title' => [
                'nl' => 'product title nl',
            ],
        ]);
        $variant = $this->catalogContext->createVariant('product-aaa', 'variant-aaa', [], ['title' => null, 'option_title' => null]);

        $productDetail = $this->catalogContext->catalogRepos()->productDetailRepository()->findProductDetail($product->getVariants()[0]->variantId);
        $this->assertEquals('product title nl', $productDetail->getTitle());
    }

    public function test_if_variant_title_is_empty_it_uses_product_title_and_option_title()
    {
        $product = $this->catalogContext->createProduct('product-aaa', null, [], [
            'title' => [
                'nl' => 'product title nl',
            ],
        ]);
        $variant = $this->catalogContext->createVariant('product-aaa', 'variant-aaa', [], ['title' => null]);

        $productDetail = $this->catalogContext->catalogRepos()->productDetailRepository()->findProductDetail($product->getVariants()[0]->variantId);
        $this->assertEquals('product title nl variant-aaa option title nl', $productDetail->getTitle());
    }

    public function test_it_can_add_images()
    {
        $product = $this->catalogContext->createProduct();

        $productDetail = $this->catalogContext->catalogRepos()->productDetailRepository()->findProductDetail($product->getVariants()[0]->variantId);
        $productDetail->setImages(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $productDetail->getImages());
    }

    public function test_it_can_get_stockable_info()
    {
        $product = $this->catalogContext->createProduct();

        $stockable = $this->catalogContext->catalogRepos()->productDetailRepository()->findProductDetail($product->getVariants()[0]->variantId);

        $this->assertEquals(5, $stockable->getStockLevel());
        $this->assertEquals(false, $stockable->ignoresOutOfStock());
        $this->assertEquals(true, $stockable->inStock());
    }
}
