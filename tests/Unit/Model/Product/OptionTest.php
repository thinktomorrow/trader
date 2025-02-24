<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Product;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

class OptionTest extends TestCase
{
    public function test_a_variant_can_have_option_values()
    {
        $variant = Variant::create(
            $productId = ProductId::fromString('xxx'),
            $variantId = VariantId::fromString('yyy'),
            $productUnitPrice = VariantUnitPrice::fromMoney(
                Money::EUR(10),
                VatPercentage::fromString('20'),
                false
            ),
            $productSalePrice = VariantSalePrice::fromMoney(Money::EUR(8), VatPercentage::fromString('20'), false),
            'sku',
        );

        $variant->updateOptionValueIds([
            OptionValueId::fromString('def'),
        ]);

        $this->assertEquals([
            'def',
        ], $variant->getMappedData()['option_value_ids']);
    }
}
