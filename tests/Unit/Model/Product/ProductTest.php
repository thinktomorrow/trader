<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Product;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductDataUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantDeleted;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantKeyCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantKeyUpdated;
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
use Thinktomorrow\Trader\Domain\Model\Product\VariantKey\VariantKey;
use Thinktomorrow\Trader\Domain\Model\Product\VariantKey\VariantKeyId;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class ProductTest extends TestCase
{
    public function test_it_can_create_a_product()
    {
        $product = Product::create(ProductId::fromString('product-aaa'));

        $this->assertEquals([
            'product_id' => 'product-aaa',
            'state' => ProductState::offline->value,
            'data' => '[]',
        ], $product->getMappedData());

        $this->assertEquals(
            new ProductCreated(ProductId::fromString('product-aaa')),
            $product->releaseEvents()[0]
        );
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
            'product_id' => 'product-aaa',
            'state' => ProductState::offline->value,
            'data' => $data,
        ]);

        $this->assertEquals(ProductId::fromString('product-aaa'), $product->getMappedData()['product_id']);
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
            'product_id' => 'product-aaa',
            'state' => ProductState::online->value,
            'data' => json_encode(['title' => ['nl' => 'A title']]),
        ], [
            Variant::class => [
                [
                    [
                        'product_id' => 'product-aaa',
                        'variant_id' => 'variant-aaa',
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
                                'variant_id' => 'variant-aaa',
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
                    'product_id' => 'product-aaa',
                    'taxon_id' => 'variant-prop',
                    'state' => TaxonState::online->value,
                    'data' => json_encode(['foo' => 'bar']),
                    'taxonomy_type' => TaxonomyType::variant_property->value,
                ],
                [
                    'product_id' => 'product-aaa',
                    'taxon_id' => 'category',
                    'state' => TaxonState::online->value,
                    'data' => json_encode(['foo' => 'baz']),
                    'taxonomy_type' => TaxonomyType::category->value,
                ],
            ],
            Personalisation::class => [
                [
                    'product_id' => 'product-aaa',
                    'personalisation_id' => 'pers-1',
                    'personalisation_type' => PersonalisationType::TEXT,
                    'data' => json_encode(['foo' => 'bar']),
                ],
            ],
        ]);

        $this->assertCount(1, $product->getVariants());
        $this->assertEquals(VariantId::fromString('variant-aaa'), $product->getVariants()[0]->variantId);

        $this->assertCount(1, $product->getVariants()[0]->getVariantProperties());
        $this->assertInstanceOf(VariantProperty::class, $product->getVariantProperties()[0]);
        $this->assertInstanceOf(\Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantProperty::class, $product->getVariants()[0]->getVariantProperties()[0]);
        $this->assertEquals(TaxonId::fromString('variant-prop'), $product->getVariants()[0]->getVariantProperties()[0]->taxonId);

        $this->assertCount(2, $product->getProductTaxa());
        $this->assertEquals(TaxonId::fromString('category'), $product->getProductTaxa()[1]->taxonId);
        $this->assertCount(1, $product->getVariantProperties());
        $this->assertEquals(TaxonId::fromString('variant-prop'), $product->getVariantProperties()[0]->taxonId);

        $this->assertCount(1, $product->getPersonalisations());
        $this->assertEquals(PersonalisationId::fromString('pers-1'), $product->getPersonalisations()[0]->personalisationId);
        $this->assertEquals(['foo' => 'bar'], $product->getPersonalisations()[0]->getData());
    }

    public function test_it_can_update_state()
    {
        $product = Product::create(ProductId::fromString('product-aaa'));

        $product->updateState(ProductState::archived);

        $this->assertEquals(ProductState::archived->value, $product->getMappedData()['state']);
    }

    public function test_it_records_event_when_data_is_updated()
    {
        $product = Product::create(ProductId::fromString('product-aaa'));
        $product->releaseEvents();

        $product->addData(['title' => ['nl' => 'updated title']]);

        $this->assertEquals([
            new ProductDataUpdated(ProductId::fromString('product-aaa')),
        ], $product->releaseEvents());
    }

    public function test_it_can_add_variant()
    {
        $product = Product::create(ProductId::fromString('product-aaa'));
        $product->createVariant(Variant::create(
            ProductId::fromString('product-aaa'),
            VariantId::fromString('variant-aaa'),
            VariantUnitPrice::fromScalars('100', '20', true),
            VariantSalePrice::fromScalars('80', '20', true),
            'sku',
        ));

        $events = $product->releaseEvents();

        $this->assertTrue(in_array(new ProductCreated(ProductId::fromString('product-aaa')), $events));
        $this->assertTrue(in_array(new VariantCreated(ProductId::fromString('product-aaa'), VariantId::fromString('variant-aaa')), $events));
    }

    public function test_it_cannot_add_same_variant_twice()
    {
        $this->expectException(VariantAlreadyExistsOnProduct::class);

        $product = Product::create(ProductId::fromString('product-aaa'));
        $product->createVariant(Variant::create(
            ProductId::fromString('product-aaa'),
            VariantId::fromString('variant-aaa'),
            VariantUnitPrice::fromScalars('100', '20', true),
            VariantSalePrice::fromScalars('80', '20', true),
            'sku',
        ));

        $product->createVariant($product->getVariants()[0]);
    }

    public function test_it_cannot_add_variant_of_other_product()
    {
        $this->expectException(\InvalidArgumentException::class);

        $product = Product::create(ProductId::fromString('product-aaa'));

        $product->createVariant(Variant::create(
            ProductId::fromString('false-product-id'),
            VariantId::fromString('variant-aaa'),
            VariantUnitPrice::fromScalars('100', '20', true),
            VariantSalePrice::fromScalars('80', '20', true),
            'sku',
        ));
    }

    public function test_it_can_update_variant()
    {
        $product = Product::create(ProductId::fromString('product-aaa'));

        $product->createVariant(Variant::create(
            ProductId::fromString('product-aaa'),
            VariantId::fromString('variant-aaa'),
            VariantUnitPrice::fromScalars('100', '20', true),
            VariantSalePrice::fromScalars('80', '20', true),
            'sku',
        ));

        $variant = $product->getVariants()[0];
        $variant->updatePrice(VariantUnitPrice::fromScalars('150', '20', true), VariantSalePrice::fromScalars('120', '20', true));

        $product->updateVariant($variant);

        $this->assertEquals('150', $product->getChildEntities()[Variant::class][0]['unit_price']);
        $this->assertEquals('120', $product->getChildEntities()[Variant::class][0]['sale_price']);
    }

    public function test_it_records_event_when_variant_is_updated()
    {
        $product = Product::create(ProductId::fromString('product-aaa'));

        $product->createVariant(Variant::create(
            ProductId::fromString('product-aaa'),
            VariantId::fromString('variant-aaa'),
            VariantUnitPrice::fromScalars('100', '20', true),
            VariantSalePrice::fromScalars('80', '20', true),
            'sku',
        ));

        $product->releaseEvents();

        $variant = $product->getVariants()[0];
        $variant->updatePrice(VariantUnitPrice::fromScalars('10', '21', false), VariantSalePrice::fromScalars('8', '21', false));

        $product->updateVariant($variant);

        $this->assertEquals([
            new VariantUpdated(ProductId::fromString('product-aaa'), VariantId::fromString('variant-aaa')),
        ], $product->releaseEvents());
    }

    public function test_it_cannot_update_unknown_variant()
    {
        $this->expectException(CouldNotFindVariantOnProduct::class);

        $product = Product::create(ProductId::fromString('product-aaa'));

        $product->updateVariant(Variant::create(
            ProductId::fromString('product-aaa'),
            VariantId::fromString('unknown'),
            VariantUnitPrice::fromScalars('10', '21', false),
            VariantSalePrice::fromScalars('8', '21', false),
            'other-sku',
        ));
    }

    public function test_it_cannot_update_variant_of_other_product()
    {
        $this->expectException(\InvalidArgumentException::class);

        $product = Product::create(ProductId::fromString('product-aaa'));

        $product->updateVariant(Variant::create(
            ProductId::fromString('other-product'),
            VariantId::fromString('zzz'),
            VariantUnitPrice::fromScalars('10', '21', false),
            VariantSalePrice::fromScalars('8', '21', false),
            'sku',
        ));
    }

    public function test_it_can_delete_variant()
    {
        $product = Product::create(ProductId::fromString('product-aaa'));
        $product->createVariant(Variant::create(
            ProductId::fromString('product-aaa'),
            VariantId::fromString('variant-aaa'),
            VariantUnitPrice::fromScalars('100', '20', true),
            VariantSalePrice::fromScalars('80', '20', true),
            'sku',
        ));

        $product->createVariant(Variant::create(
            ProductId::fromString('product-aaa'),
            VariantId::fromString('variant-zzz'),
            VariantUnitPrice::fromScalars('100', '20', true),
            VariantSalePrice::fromScalars('80', '20', true),
            'sku',
        ));

        $product->deleteVariant(VariantId::fromString('variant-zzz'));

        $events = $product->releaseEvents();

        $this->assertTrue(in_array(new ProductCreated(ProductId::fromString('product-aaa')), $events));
        $this->assertTrue(in_array(new VariantCreated(ProductId::fromString('product-aaa'), VariantId::fromString('variant-aaa')), $events));
        $this->assertTrue(in_array(new VariantCreated(ProductId::fromString('product-aaa'), VariantId::fromString('variant-zzz')), $events));
        $this->assertTrue(in_array(new VariantDeleted(ProductId::fromString('product-aaa'), VariantId::fromString('variant-zzz')), $events));
    }

    public function test_it_cannot_delete_last_variant()
    {
        $this->expectException(CouldNotDeleteVariant::class);

        $product = Product::create(ProductId::fromString('product-aaa'));

        $product->createVariant(Variant::create(
            ProductId::fromString('product-aaa'),
            VariantId::fromString('variant-aaa'),
            VariantUnitPrice::fromScalars('100', '20', true),
            VariantSalePrice::fromScalars('80', '20', true),
            'sku',
        ));

        $product->deleteVariant(VariantId::fromString('variant-aaa'));

        $this->assertCount(1, $product->getVariants());
    }

    public function test_it_can_release_event_created_variant_key()
    {
        $product = Product::create(ProductId::fromString('product-aaa'));
        $product->createVariant($variant = Variant::create(
            ProductId::fromString('product-aaa'),
            VariantId::fromString('variant-aaa'),
            VariantUnitPrice::fromScalars('100', '20', true),
            VariantSalePrice::fromScalars('80', '20', true),
            'sku',
        ));

        // Drop existing events
        $product->releaseEvents();

        $variant->updateVariantKeys([
            $variantKey = VariantKey::create($variant->variantId, VariantKeyId::fromString('xxx'), Locale::fromString('nl_BE')),
        ]);

        $product->updateVariant($variant);

        $this->assertEquals([
            new VariantKeyCreated($variant->variantId, Locale::fromString('nl_BE'), VariantKeyId::fromString('xxx')),
            new VariantUpdated($product->productId, $variant->variantId),
        ], $product->releaseEvents());
    }

    public function test_it_can_release_event_updated_variant_key()
    {
        $product = Product::create(ProductId::fromString('product-aaa'));
        $product->createVariant($variant = Variant::create(
            ProductId::fromString('product-aaa'),
            VariantId::fromString('variant-aaa'),
            VariantUnitPrice::fromScalars('100', '20', true),
            VariantSalePrice::fromScalars('80', '20', true),
            'sku',
        ));

        $variant->updateVariantKeys([
            $variantKey = VariantKey::create($variant->variantId, VariantKeyId::fromString('xxx'), Locale::fromString('nl_BE')),
        ]);

        // Drop existing events
        $product->releaseEvents();
        $variant->releaseEventsForAggregate();

        // Update
        $variant->updateVariantKeys([
            $variantKey = VariantKey::create($variant->variantId, VariantKeyId::fromString('yyy'), Locale::fromString('nl_BE')),
        ]);

        $product->updateVariant($variant);

        $this->assertEquals([
            new VariantKeyUpdated($variant->variantId, Locale::fromString('nl_BE'), VariantKeyId::fromString('xxx'), VariantKeyId::fromString('yyy')),
            new VariantUpdated($product->productId, $variant->variantId),
        ], $product->releaseEvents());
    }
}
