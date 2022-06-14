<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Money\Money;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductTaxaUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Event\VariantCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Event\VariantDeleted;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotDeleteVariant;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;

class ProductTest extends TestCase
{
    /** @test */
    public function it_can_create_a_product()
    {
        $product = $this->createdProduct();

        $this->assertEquals([
            'product_id' => 'xxx',
            'state' => ProductState::online->value,
            'taxon_ids' => [],
            'data' => '[]',
        ], $product->getMappedData());

        $this->assertEquals([
            new ProductCreated(ProductId::fromString('xxx')),
        ], $product->releaseEvents());
    }

    /** @test */
    public function it_can_be_build_from_raw_data()
    {
        $data = json_encode([
            'title' => [
                'nl' => 'title nl',
                'en' => 'title en',
            ],
            'custom' => 'custom-value',
        ]);

        $product = Product::fromMappedData([
            'product_id' => 'xxx',
            'state' => ProductState::offline->value,
            'data' => $data,
            'taxon_ids' => ['1','2'],
        ]);

        $this->assertEquals(ProductId::fromString('xxx'), $product->getMappedData()['product_id']);
        $this->assertEquals(ProductState::offline, $product->getState());
        $this->assertEquals(['1','2'], $product->getMappedData()['taxon_ids']);
        $this->assertEquals([
            'nl' => 'title nl',
            'en' => 'title en',
        ], $product->getData('title'));
        $this->assertEquals($data, $product->getMappedData()['data']);
    }

    /** @test */
    public function it_can_add_taxon()
    {
        $product = $this->createdProduct();

        $product->updateTaxonIds([TaxonId::fromString('zzz')]);

        $this->assertEquals([
            new ProductCreated(ProductId::fromString('xxx')),
            new ProductTaxaUpdated(ProductId::fromString('xxx')),
        ], $product->releaseEvents());

        $this->assertEquals(['zzz'], $product->getMappedData()['taxon_ids']);
    }

    /** @test */
    public function it_can_add_variant()
    {
        $product = $this->createdProductWithVariant();

        $this->assertEquals([
            new ProductCreated(ProductId::fromString('xxx')),
            new VariantCreated(ProductId::fromString('xxx'), VariantId::fromString('yyy')),
        ], $product->releaseEvents());
    }

    /** @test */
    public function it_cannot_add_variant_of_other_product()
    {
        $this->expectException(\InvalidArgumentException::class);

        $product = $this->createdProduct();

        $product->createVariant(Variant::create(
            ProductId::fromString('false-product-id'),
            VariantId::fromString('yyy'),
            VariantUnitPrice::zero(),
            VariantSalePrice::zero(),
        ));
    }

    /** @test */
    public function it_can_update_state()
    {
        $product = $this->createdProduct();

        $product->updateState(ProductState::archived);

        $this->assertEquals(ProductState::archived->value, $product->getMappedData()['state']);
    }

    /** @test */
    public function it_can_update_variant()
    {
        $product = $this->createdProductWithVariant();

        $variant = $product->getVariants()[0];
        $variant->updatePrice(VariantUnitPrice::zero(), VariantSalePrice::zero());

        $product->updateVariant($variant);

        $this->assertEquals('0', $product->getChildEntities()[Variant::class][0]['unit_price']);
        $this->assertEquals('0', $product->getChildEntities()[Variant::class][0]['sale_price']);
        $this->assertEquals([
            'option-value-id',
        ], $product->getVariants()[0]->getMappedData()['option_value_ids']);
    }

    /** @test */
    public function it_can_delete_variant()
    {
        $product = $this->createdProductWithVariant();
        $product->createVariant(Variant::create(
            ProductId::fromString('xxx'),
            VariantId::fromString('zzz'),
            VariantUnitPrice::fromMoney(Money::EUR(10), TaxRate::fromString('20'), false),
            VariantSalePrice::fromMoney(Money::EUR(8), TaxRate::fromString('20'), false),
        ));

        $product->deleteVariant(VariantId::fromString('zzz'));

        $this->assertEquals([
            new ProductCreated(ProductId::fromString('xxx')),
            new VariantCreated(ProductId::fromString('xxx'), VariantId::fromString('yyy')),
            new VariantCreated(ProductId::fromString('xxx'), VariantId::fromString('zzz')),
            new VariantDeleted(ProductId::fromString('xxx'), VariantId::fromString('zzz')),
        ], $product->releaseEvents());
    }

    /** @test */
    public function it_cannot_delete_last_variant()
    {
        $this->expectException(CouldNotDeleteVariant::class);

        $product = $this->createdProductWithVariant();

        $product->deleteVariant(VariantId::fromString('yyy'));

        $this->assertCount(1, $product->getVariants());
    }
}
