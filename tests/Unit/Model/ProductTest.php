<?php
declare(strict_types=1);

namespace Tests\Unit;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductUnitPrice;
use Thinktomorrow\Trader\Domain\Model\ProductGroup\ProductGroupId;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductUpdated;

class ProductTest extends TestCase
{
    /** @test */
    public function it_can_create_a_product_entity()
    {
        $product = Product::create(
            $productId = ProductId::fromString('yyy'),
            $productGroupId = ProductGroupId::fromString('xxx'),
            $productUnitPrice = ProductUnitPrice::fromMoneyExcludingVat(
                Money::EUR(10), TaxRate::fromString('20')
            ),
        );

        $this->assertEquals([
            'product_id' => $productId->get(),
            'product_group_id' => $productGroupId->get(),
            'product_unit_price' => $productUnitPrice->getExcludingVat()->getAmount(),
            'tax_rate' => $productUnitPrice->getTaxRate()->toPercentage()->get(),
            'includes_vat' => false,
            'data' => [],
        ], $product->getMappedData());

        $this->assertEquals([
            new ProductCreated($productId),
        ], $product->releaseEvents());
    }

    /** @test */
    public function it_can_update_a_product_entity()
    {
        $product = $this->createdProduct();

        $product->update(
            ProductGroupId::fromString('zzz'),
            ProductUnitPrice::fromMoneyExcludingVat(Money::EUR(10),
            TaxRate::fromString('20')),
            ['foo' => 'bar']
        );

        $this->assertEquals(ProductGroupId::fromString('zzz'), $product->getMappedData()['product_group_id']);
        $this->assertEquals(['foo' => 'bar'], $product->getMappedData()['data']);

        $this->assertEquals([
            new ProductCreated($product->productId),
            new ProductUpdated($product->productId),
        ], $product->releaseEvents());
    }

    /** @test */
    public function updating_data_merges_with_existing_data()
    {
        $product = $this->createdProduct();

        $product->update(
            ProductGroupId::fromString('zzz'),
            ProductUnitPrice::fromMoneyExcludingVat(Money::EUR(10),
                TaxRate::fromString('20')),
            ['bar' => 'baz']
        );

        $product->update(
            ProductGroupId::fromString('zzz'),
            ProductUnitPrice::fromMoneyExcludingVat(Money::EUR(10),
                TaxRate::fromString('20')),
            ['foo' => 'bar']
        );

        $this->assertEquals(['bar' => 'baz', 'foo' => 'bar'], $product->getMappedData()['data']);
    }

    /** @test */
    public function it_can_be_build_from_raw_data()
    {
        $product = Product::fromMappedData([
            'product_id' => 'xxx',
            'product_group_id' => 'yyy',
            'product_unit_price' => 100,
            'tax_rate' => '20',
            'includes_vat' => false,
            'data' => ['foo' => 'bar']
        ]);

        $this->assertEquals(ProductId::fromString('xxx'), $product->productId);
        $this->assertEquals(ProductGroupId::fromString('yyy'), $product->getMappedData()['product_group_id']);
        $this->assertEquals(100, $product->getMappedData()['product_unit_price']);
        $this->assertEquals('20', $product->getMappedData()['tax_rate']);
        $this->assertEquals(false, $product->getMappedData()['includes_vat']);
        $this->assertEquals(['foo' => 'bar'], $product->getMappedData()['data']);
    }

    private function createdProduct(): Product
    {
        return Product::create(
            ProductId::fromString('yyy'),
            ProductGroupId::fromString('xxx'),
            ProductUnitPrice::fromMoneyExcludingVat(
                Money::EUR(10), TaxRate::fromString('20')
            )
        );
    }
}
