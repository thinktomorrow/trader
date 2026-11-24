<?php

declare(strict_types=1);

namespace Tests\Unit\Promo;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Application\Promo\Preview\PromoPreviewProduct;
use Thinktomorrow\Trader\Domain\Model\Order\Line\PurchasableReference;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

final class PromoPreviewProductTest extends TestCase
{
    public function test_product_detail_supplies_the_same_preview_fields_as_a_cart_line(): void
    {
        $taxon = $this->createMock(ProductTaxonItem::class);
        $taxon->method('getTaxonId')->willReturn('taxon-1');
        $product = $this->createMock(ProductDetail::class);
        $product->method('getProductId')->willReturn('product-1');
        $product->method('getVariantId')->willReturn('variant-1');
        $product->method('getTaxa')->willReturn([$taxon]);
        $product->method('getUnitPrice')->willReturn(VariantUnitPrice::fromScalars('10000', '21', false));
        $product->method('getSalePrice')->willReturn(VariantSalePrice::fromScalars('9000', '21', false));

        $preview = PromoPreviewProduct::fromProductDetail($product, 2);

        $this->assertSame('variant-1', $preview->variantId);
        $this->assertSame(['taxon-1'], $preview->taxonIds);
        $this->assertTrue($preview->isOnSale());
        $this->assertSame(2, $preview->quantityToAdd);
    }

    public function test_a_discounted_cart_line_is_not_mistaken_for_a_sale_product(): void
    {
        $line = $this->createMock(CartLine::class);
        $line->method('getPurchasableReference')->willReturn(new PurchasableReference('variant', 'variant-1'));
        $line->method('getUnitPriceExcl')->willReturn(Money::EUR(10000));
        $line->method('getDiscountedUnitPriceExcl')->willReturn(Money::EUR(9000));
        $line->method('getData')->willReturnCallback(fn (?string $key = null, $default = null) => [
            'product_id' => 'product-1',
            'sale_price_excl' => '10000',
            'taxa' => [['taxon_id' => 'taxon-1']],
        ][$key] ?? $default);

        $product = PromoPreviewProduct::fromCartLine($line);

        $this->assertFalse($product->isOnSale());
        $this->assertSame('product-1', $product->productId);
        $this->assertSame(['taxon-1'], $product->taxonIds);
        $this->assertSame(0, $product->quantityToAdd);
    }

    public function test_missing_sale_price_falls_back_to_unit_price(): void
    {
        $line = $this->createMock(CartLine::class);
        $line->method('getPurchasableReference')->willReturn(new PurchasableReference('variant', 'variant-1'));
        $line->method('getUnitPriceExcl')->willReturn(Money::EUR(10000));
        $line->method('getData')->willReturnCallback(fn (?string $key = null, $default = null) => $default);

        $this->assertFalse(PromoPreviewProduct::fromCartLine($line)->isOnSale());
    }
}
