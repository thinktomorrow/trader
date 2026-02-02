<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Money\Money;
use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Product\VariantKey\VariantKey;
use Thinktomorrow\Trader\Domain\Model\Product\VariantKey\VariantKeyId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultGridItem;

class GridItemTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_get_a_grid_item()
    {
        $gridItem = DefaultGridItem::fromMappedData([
            'product_id' => '123',
            'variant_id' => '456',
            'state' => 'available',
            'sale_price' => 800,
            'unit_price' => 1000,
            'tax_rate' => '21',
            'includes_vat' => true,
            'product_data' => json_encode(['title' => ['nl' => 'Test Product', 'en' => 'Test Product EN']]),
            'data' => json_encode(['some_key' => 'some_value']),
            'variant_data' => json_encode(['option_title' => ['nl' => 'Variant Option Title NL', 'en' => 'Variant Option Title EN']]),
            'sku' => 'test-sku',
            'ean' => null,
        ], [], []);

        $this->assertEquals('456', $gridItem->getVariantId());
        $this->assertEquals('123', $gridItem->getProductId());
        $this->assertEquals('Test Product', $gridItem->getTitle());
        $this->assertCount(0, $gridItem->getTaxa());
        $this->assertTrue($gridItem->isAvailable());

        $this->assertEquals(VariantUnitPrice::fromMoney(Money::EUR('1000'), VatPercentage::fromString('21'), true), $gridItem->getUnitPrice());
        $this->assertEquals(VariantSalePrice::fromMoney(Money::EUR('800'), VatPercentage::fromString('21'), true), $gridItem->getSalePrice());
        $this->assertEquals(VariantUnitPrice::fromMoney(Money::EUR('200'), VatPercentage::fromString('21'), true), $gridItem->getSaleDiscountPrice());
        $this->assertEquals('€ 8,26', $gridItem->getFormattedUnitPriceExcl());
        $this->assertEquals('€ 10', $gridItem->getFormattedUnitPriceIncl());
        $this->assertEquals('€ 6,61', $gridItem->getFormattedSalePriceExcl());
        $this->assertEquals('€ 8', $gridItem->getFormattedSalePriceIncl());
        $this->assertEquals('€ 1,65', $gridItem->getFormattedSaleDiscountPriceExcl());
        $this->assertEquals('€ 2', $gridItem->getFormattedSaleDiscountPriceIncl());
        $this->assertEquals('21', $gridItem->getFormattedVatRate());

        $this->assertTrue($gridItem->onSale());
        $this->assertEquals(20, $gridItem->getSaleDiscountPercentage());
    }

    public function test_it_can_get_a_grid_item_variant_keys()
    {
        $gridItem = DefaultGridItem::fromMappedData([
            'product_id' => '123',
            'variant_id' => '456',
            'state' => 'available',
            'sale_price' => 800,
            'unit_price' => 1000,
            'tax_rate' => '21',
            'includes_vat' => true,
            'product_data' => json_encode(['title' => ['nl' => 'Test Product', 'en' => 'Test Product EN']]),
            'data' => json_encode(['some_key' => 'some_value']),
            'variant_data' => json_encode(['option_title' => ['nl' => 'Variant Option Title NL', 'en' => 'Variant Option Title EN']]),
            'sku' => 'test-sku',
            'ean' => null,
        ], [], [
            VariantKey::create(VariantId::fromString('456'), VariantKeyId::fromString('xxx'), Locale::fromString('nl')),
        ]);

        // Check private property $variantKeys via reflection
        $reflection = new \ReflectionClass($gridItem);
        $property = $reflection->getProperty('variantKeys');

        $variantKeys = $property->getValue($gridItem);
        $this->assertCount(1, $variantKeys);
        $this->assertEquals('xxx', $variantKeys[0]->getKey());
    }
}
