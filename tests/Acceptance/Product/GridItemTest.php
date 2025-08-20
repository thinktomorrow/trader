<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
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
        ], []);

        $this->assertEquals('456', $gridItem->getVariantId());
        $this->assertEquals('123', $gridItem->getProductId());
        $this->assertEquals('Test Product', $gridItem->getTitle());
        $this->assertCount(0, $gridItem->getTaxa());
        $this->assertTrue($gridItem->isAvailable());
        $this->assertEquals('€ 10', $gridItem->getUnitPrice());
        $this->assertEquals('€ 8,26', $gridItem->getUnitPrice(false));
        $this->assertEquals('€ 8', $gridItem->getSalePrice());
        $this->assertEquals('€ 6,61', $gridItem->getSalePrice(false));
        $this->assertEquals('21', $gridItem->getTaxRateAsString());
        $this->assertTrue($gridItem->onSale());
        $this->assertEquals('€ 2', $gridItem->getSaleDiscount());
    }
}
