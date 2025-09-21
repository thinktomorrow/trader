<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Product;

use Money\Money;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductDataUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantDeleted;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotDeleteVariant;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindVariantOnProduct;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\VariantAlreadyExistsOnProduct;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationType;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\VariantProperty;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class ProductTest extends TestCase
{
    public function test_it_can_create_a_product()
    {
        $product = $this->createProduct();

        $this->assertEquals([
            'product_id' => 'xxx',
            'state' => ProductState::online->value,
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
        ]);

        $this->assertEquals(ProductId::fromString('xxx'), $product->getMappedData()['product_id']);
        $this->assertEquals(ProductState::offline, $product->getState());
        $this->assertEquals([
            'nl' => 'title nl',
            'en' => 'title en',
        ], $product->getData('title'));
        $this->assertEquals($data, $product->getMappedData()['data']);
    }

    public function test_it_can_be_build_from_raw_data_with_child_entities()
    {
        $product = Product::fromMappedData([
            'product_id' => 'xxx',
            'state' => ProductState::online->value,
            'data' => json_encode(['title' => ['nl' => 'A title']]),
        ], [
            Variant::class => [
                [
                    [
                        'product_id' => 'xxx',
                        'variant_id' => 'yyy',
                        'state' => VariantState::available->value,
                        'unit_price' => 100,
                        'sale_price' => 90,
                        'tax_rate' => '21',
                        'includes_vat' => true,
                        'sku' => 'sku',
                        'ean' => 'ean',
                        'show_in_grid' => true,
                        'data' => json_encode(['foo' => 'bar']),
                    ],
                    [
                        VariantTaxon::class => [
                            [
                                'variant_id' => 'yyy',
                                'taxon_id' => 'variant-prop',
                                'state' => TaxonState::online->value,
                                'data' => json_encode(['label' => 'prop']),
                                'taxonomy_type' => TaxonomyType::variant_property->value,
                            ],
                        ],
                    ],
                ],
            ],
            ProductTaxon::class => [
                [
                    'product_id' => 'xxx',
                    'taxon_id' => 'regular',
                    'state' => TaxonState::online->value,
                    'data' => json_encode(['foo' => 'bar']),
                ],
                [
                    'product_id' => 'xxx',
                    'taxon_id' => 'variant-prop',
                    'state' => TaxonState::online->value,
                    'data' => json_encode(['foo' => 'baz']),
                    'taxonomy_type' => TaxonomyType::variant_property->value,
                ],
            ],
            Personalisation::class => [
                [
                    'product_id' => 'xxx',
                    'personalisation_id' => 'pers-1',
                    'personalisation_type' => PersonalisationType::TEXT,
                    'data' => json_encode(['foo' => 'bar']),
                ],
            ],
        ]);

        $this->assertCount(1, $product->getVariants());
        $this->assertEquals(VariantId::fromString('yyy'), $product->getVariants()[0]->variantId);
        $this->assertCount(1, $product->getVariants()[0]->getVariantProperties());
        $this->assertInstanceOf(VariantProperty::class, $product->getVariants()[0]->getVariantProperties()[0]);
        $this->assertEquals(TaxonId::fromString('variant-prop'), $product->getVariants()[0]->getVariantProperties()[0]->taxonId);

        $this->assertCount(2, $product->getProductTaxa());
        $this->assertEquals(TaxonId::fromString('regular'), $product->getProductTaxa()[0]->taxonId);
        $this->assertCount(1, $product->getVariantProperties());
        $this->assertEquals(TaxonId::fromString('variant-prop'), $product->getVariantProperties()[0]->taxonId);

        $this->assertCount(1, $product->getPersonalisations());
        $this->assertEquals(PersonalisationId::fromString('pers-1'), $product->getPersonalisations()[0]->personalisationId);
        $this->assertEquals(['foo' => 'bar'], $product->getPersonalisations()[0]->getData());
    }

    public function test_it_can_update_state()
    {
        $product = $this->createProduct();

        $product->updateState(ProductState::archived);

        $this->assertEquals(ProductState::archived->value, $product->getMappedData()['state']);
    }

    public function test_it_records_event_when_data_is_updated()
    {
        $product = $this->createProduct();
        $product->releaseEvents();

        $product->addData(['title' => ['nl' => 'updated title']]);

        $this->assertEquals([
            new ProductDataUpdated(ProductId::fromString('xxx')),
        ], $product->releaseEvents());
    }

    public function test_it_can_add_variant()
    {
        $product = $this->createProductWithVariant();

        $this->assertEquals([
            new ProductCreated(ProductId::fromString('xxx')),
            new VariantCreated(ProductId::fromString('xxx'), VariantId::fromString('yyy')),
        ], $product->releaseEvents());
    }

    public function test_it_cannot_add_same_variant_twice()
    {
        $this->expectException(VariantAlreadyExistsOnProduct::class);

        $product = $this->createProductWithVariant();

        $product->createVariant($product->getVariants()[0]);
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

    public function test_it_can_update_variant()
    {
        $product = $this->createProductWithVariant();

        $variant = $product->getVariants()[0];
        $variant->updatePrice(VariantUnitPrice::zero(), VariantSalePrice::zero());

        $product->updateVariant($variant);

        $this->assertEquals('0', $product->getChildEntities()[Variant::class][0]['unit_price']);
        $this->assertEquals('0', $product->getChildEntities()[Variant::class][0]['sale_price']);
    }

    public function test_it_records_event_when_variant_is_updated()
    {
        $product = $this->createProductWithVariant();
        $product->releaseEvents();

        $variant = $product->getVariants()[0];
        $variant->updatePrice(VariantUnitPrice::zero(), VariantSalePrice::zero());

        $product->updateVariant($variant);

        $this->assertEquals([
            new VariantUpdated(ProductId::fromString('xxx'), VariantId::fromString('yyy')),
        ], $product->releaseEvents());
    }

    public function test_it_cannot_update_unknown_variant()
    {
        $this->expectException(CouldNotFindVariantOnProduct::class);

        $product = $this->createProductWithVariant();

        $product->updateVariant(Variant::create(
            ProductId::fromString('xxx'),
            VariantId::fromString('unknown'),
            VariantUnitPrice::zero(),
            VariantSalePrice::zero(),
            'other-sku',
        ));
    }

    public function test_it_cannot_update_variant_of_other_product()
    {
        $this->expectException(\InvalidArgumentException::class);

        $product = $this->createProductWithVariant();

        $product->updateVariant(Variant::create(
            ProductId::fromString('other-product'),
            VariantId::fromString('zzz'),
            VariantUnitPrice::zero(),
            VariantSalePrice::zero(),
            'sku',
        ));
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
