<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Event\VariantAdded;
use Thinktomorrow\Trader\Domain\Model\Product\Event\VariantUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;

class VariantTest extends TestCase
{
    /** @test */
    public function it_can_create_a_variant()
    {
        $variant = Variant::create(
            $productId = ProductId::fromString('xxx'),
            $variantId = VariantId::fromString('yyy'),
            $productUnitPrice = VariantUnitPrice::fromMoney(
                Money::EUR(10), TaxRate::fromString('20'), false
            ),
            $productSalePrice = VariantSalePrice::fromMoney(Money::EUR(8), TaxRate::fromString('20'), false),
        );

        $this->assertEquals([
            'product_id' => $productId->get(),
            'variant_id' => $variantId->get(),
            'unit_price' => $productUnitPrice->getExcludingVat()->getAmount(),
            'sale_price' => $productSalePrice->getExcludingVat()->getAmount(),
            'tax_rate' => $productUnitPrice->getTaxRate()->toPercentage()->get(),
            'includes_vat' => false,
            'options' => [],
            'data' => [],
        ], $variant->getMappedData());
    }

    /** @test */
    public function it_can_update_a_variant_price()
    {
        $variant = $this->createdVariant();

        $variant->updatePrice(
            $unitPrice = VariantUnitPrice::fromMoney(Money::EUR(10), TaxRate::fromString('20'), false),
            $salePrice = VariantSalePrice::fromMoney(Money::EUR(8), TaxRate::fromString('20'), false),
        );

        $this->assertEquals($unitPrice->getMoney()->getAmount(), $variant->getMappedData()['unit_price']);
        $this->assertEquals($salePrice->getMoney()->getAmount(), $variant->getMappedData()['sale_price']);
        $this->assertEquals($salePrice->getTaxRate()->toPercentage()->get(), $variant->getMappedData()['tax_rate']);
        $this->assertEquals($salePrice->includesTax(), $variant->getMappedData()['includes_vat']);
        $this->assertEquals($salePrice, $variant->getSalePrice());
    }

    /** @test */
    public function it_can_update_variant_data()
    {
        $variant = $this->createdVariant();

        $variant->addData(
            ['foo' => 'bar']
        );

        $this->assertEquals(['foo' => 'bar'], $variant->getMappedData()['data']);
    }

    /** @test */
    public function updating_data_merges_with_existing_data()
    {
        $variant = $this->createdVariant();

        $variant->addData(
            ['bar' => 'baz']
        );

        $variant->addData(
            ['foo' => 'bar']
        );

        $this->assertEquals(['bar' => 'baz', 'foo' => 'bar'], $variant->getMappedData()['data']);
    }

    /** @test */
    public function it_can_be_build_from_raw_data()
    {
        $variant = Variant::fromMappedData([
            'product_id' => 'xxx',
            'variant_id' => 'yyy',
            'unit_price' => 100,
            'sale_price' => 80,
            'tax_rate' => '20',
            'includes_vat' => false,
            'options' => [
                ['option_id' => 'eee', 'option_value_id' => 'aaa']
            ],
            'data' => ['foo' => 'bar']
        ]);

        $this->assertEquals(ProductId::fromString('xxx'), $variant->getMappedData()['product_id']);
        $this->assertEquals(VariantId::fromString('yyy'), $variant->variantId);
        $this->assertEquals(100, $variant->getMappedData()['unit_price']);
        $this->assertEquals(80, $variant->getMappedData()['sale_price']);
        $this->assertEquals('20', $variant->getMappedData()['tax_rate']);
        $this->assertEquals(false, $variant->getMappedData()['includes_vat']);
        $this->assertEquals(['foo' => 'bar'], $variant->getMappedData()['data']);
    }

    private function createdVariant(): Variant
    {
        return Variant::create(
            ProductId::fromString('xxx'),
            VariantId::fromString('yyy'),
            VariantUnitPrice::fromMoney(
                Money::EUR(10), TaxRate::fromString('20'), false
            ),
            VariantSalePrice::fromMoney(Money::EUR(8), TaxRate::fromString('20'), false),
        );
    }
}