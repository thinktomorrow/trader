<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Money\Money;
use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

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

        $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($variantId);

        $this->assertEquals($variantId->get(), $productDetail->getVariantId());
        $this->assertEquals($product->getVariants()[0]->productId->get(), $productDetail->getProductId());
        $this->assertTrue($productDetail->isAvailable());

        $this->assertEquals(VariantUnitPrice::fromMoney(Money::EUR('100'), VatPercentage::fromString('20'), false), $productDetail->getUnitPrice());
        $this->assertEquals(VariantSalePrice::fromMoney(Money::EUR('80'), VatPercentage::fromString('20'), false), $productDetail->getSalePrice());
        $this->assertEquals(VariantUnitPrice::fromMoney(Money::EUR('20'), VatPercentage::fromString('20'), false), $productDetail->getSaleDiscountPrice());
        $this->assertEquals('€ 1', $productDetail->getFormattedUnitPriceExcl());
        $this->assertEquals('€ 1,20', $productDetail->getFormattedUnitPriceIncl());
        $this->assertEquals('€ 0,80', $productDetail->getFormattedSalePriceExcl());
        $this->assertEquals('€ 0,96', $productDetail->getFormattedSalePriceIncl());
        $this->assertEquals('€ 0,20', $productDetail->getFormattedSaleDiscountPriceExcl());
        $this->assertEquals('€ 0,24', $productDetail->getFormattedSaleDiscountPriceIncl());
        $this->assertEquals('20', $productDetail->getFormattedVatRate());

        $this->assertTrue($productDetail->onSale());
        $this->assertEquals(20, $productDetail->getSaleDiscountPercentage());

        $this->assertEquals('variant-aaa title nl', $productDetail->getTitle());

        $this->assertCount(2, $productDetail->getTaxa());
        $this->assertContainsOnlyInstancesOf(ProductTaxonItem::class, $productDetail->getTaxa());
    }

    public function test_it_can_get_sku_and_ean()
    {
        $product = $this->catalogContext->createProduct();
        $this->catalogContext->repos()->productRepository()->save($product);

        $variantId = $product->getVariants()[0]->variantId;
        $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($variantId);

        // Fallback values for sku / ean
        $this->assertEquals('sku-variant-aaa', $productDetail->getSku());
        $this->assertNull($productDetail->getEan());

        // Set specific sku / ean values
        $product->getVariants()[0]->updateSku('sku-foobar');
        $product->getVariants()[0]->updateEan('ean-foobar');
        $this->catalogContext->repos()->productRepository()->save($product);

        $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($variantId);
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

        $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($product->getVariants()[0]->variantId);
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

        $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($product->getVariants()[0]->variantId);
        $this->assertEquals('product title nl variant-aaa option title nl', $productDetail->getTitle());
    }

    public function test_it_can_add_images()
    {
        $product = $this->catalogContext->createProduct();

        $productDetail = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($product->getVariants()[0]->variantId);
        $productDetail->setImages(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $productDetail->getImages());
    }

    public function test_it_can_get_stockable_info()
    {
        $product = $this->catalogContext->createProduct();

        $stockable = $this->catalogContext->repos()->productDetailRepository()->findProductDetail($product->getVariants()[0]->variantId);

        $this->assertEquals(5, $stockable->getStockLevel());
        $this->assertEquals(false, $stockable->ignoresOutOfStock());
        $this->assertEquals(true, $stockable->inStock());
    }
}
