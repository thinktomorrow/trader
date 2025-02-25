<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Product;

use Money\Money;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Product\Events\OptionsUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\OptionValuesUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductTaxaUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantDeleted;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotDeleteVariant;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;
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
    public function test_it_can_create_a_product()
    {
        $product = $this->createProduct();

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

    public function test_it_can_be_build_from_raw_data()
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
            'taxon_ids' => ['1', '2'],
        ]);

        $this->assertEquals(ProductId::fromString('xxx'), $product->getMappedData()['product_id']);
        $this->assertEquals(ProductState::offline, $product->getState());
        $this->assertEquals(['1', '2'], $product->getMappedData()['taxon_ids']);
        $this->assertEquals([
            'nl' => 'title nl',
            'en' => 'title en',
        ], $product->getData('title'));
        $this->assertEquals($data, $product->getMappedData()['data']);
    }

    public function test_it_can_add_taxon()
    {
        $product = $this->createProduct();

        $product->updateTaxonIds([TaxonId::fromString('zzz')]);

        $this->assertEquals([
            new ProductCreated(ProductId::fromString('xxx')),
            new ProductTaxaUpdated(ProductId::fromString('xxx')),
        ], $product->releaseEvents());

        $this->assertEquals(['zzz'], $product->getMappedData()['taxon_ids']);
    }

    public function test_it_can_add_variant()
    {
        $product = $this->createProductWithVariant();

        $this->assertEquals([
            new ProductCreated(ProductId::fromString('xxx')),
            new OptionsUpdated(ProductId::fromString('xxx')),
            new OptionValuesUpdated(ProductId::fromString('xxx'), OptionId::fromString('ooo')),
            new VariantCreated(ProductId::fromString('xxx'), VariantId::fromString('yyy')),
        ], $product->releaseEvents());
    }

    public function test_it_cannot_add_variant_of_other_product()
    {
        $this->expectException(\InvalidArgumentException::class);

        $product = $this->createProduct();

        $product->createVariant(Variant::create(
            ProductId::fromString('false-product-id'),
            VariantId::fromString('yyy'),
            VariantUnitPrice::zero(),
            VariantSalePrice::zero(),
            'sku',
        ));
    }

    public function test_it_can_update_state()
    {
        $product = $this->createProduct();

        $product->updateState(ProductState::archived);

        $this->assertEquals(ProductState::archived->value, $product->getMappedData()['state']);
    }

    public function test_it_can_update_variant()
    {
        $product = $this->createProductWithVariant();

        $variant = $product->getVariants()[0];
        $variant->updatePrice(VariantUnitPrice::zero(), VariantSalePrice::zero());

        $product->updateVariant($variant);

        $this->assertEquals('0', $product->getChildEntities()[Variant::class][0]['unit_price']);
        $this->assertEquals('0', $product->getChildEntities()[Variant::class][0]['sale_price']);
        $this->assertEquals([
            'ppp',
        ], $product->getVariants()[0]->getMappedData()['option_value_ids']);
    }

    public function test_it_can_delete_variant()
    {
        $product = $this->createProductWithVariant();
        $product->createVariant(Variant::create(
            ProductId::fromString('xxx'),
            VariantId::fromString('zzz'),
            VariantUnitPrice::fromMoney(Money::EUR(10), VatPercentage::fromString('20'), false),
            VariantSalePrice::fromMoney(Money::EUR(8), VatPercentage::fromString('20'), false),
            'sku',
        ));

        $product->deleteVariant(VariantId::fromString('zzz'));

        $this->assertEquals([
            new ProductCreated(ProductId::fromString('xxx')),
            new OptionsUpdated(ProductId::fromString('xxx')),
            new OptionValuesUpdated(ProductId::fromString('xxx'), OptionId::fromString('ooo')),
            new VariantCreated(ProductId::fromString('xxx'), VariantId::fromString('yyy')),
            new VariantCreated(ProductId::fromString('xxx'), VariantId::fromString('zzz')),
            new VariantDeleted(ProductId::fromString('xxx'), VariantId::fromString('zzz')),
        ], $product->releaseEvents());
    }

    public function test_it_cannot_delete_last_variant()
    {
        $this->expectException(CouldNotDeleteVariant::class);

        $product = $this->createProductWithVariant();

        $product->deleteVariant(VariantId::fromString('yyy'));

        $this->assertCount(1, $product->getVariants());
    }
}
