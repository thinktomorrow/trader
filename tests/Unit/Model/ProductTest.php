<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValue;
use Thinktomorrow\Trader\Domain\Model\Product\Event\OptionsUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;
use Thinktomorrow\Trader\Domain\Model\Product\Event\VariantCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Event\VariantDeleted;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductTaxaUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Event\OptionValuesUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindOptionOnProduct;

class ProductTest extends TestCase
{
    /** @test */
    public function it_can_create_a_product()
    {
        $product = $this->createdProduct();

        $this->assertEquals([
            'product_id' => 'xxx',
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
            'data' => $data,
            'taxon_ids' => ['1','2'],
        ]);

        $this->assertEquals(ProductId::fromString('xxx'), $product->getMappedData()['product_id']);
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
    public function it_can_add_option()
    {
        $product = $this->createdProduct();

        $product->updateOptions([Option::create($product->productId, OptionId::fromString('ooo'), ['foo' => 'bar'])]);

        $this->assertEquals([
            new ProductCreated(ProductId::fromString('xxx')),
            new OptionsUpdated(ProductId::fromString('xxx')),
        ], $product->releaseEvents());

        $this->assertEquals([
            [
                'product_id' => $product->productId->get(),
                'option_id' => 'ooo',
                'values' => [],
                'data' => json_encode(['foo' => 'bar']),
            ]
        ], $product->getChildEntities()[Option::class]);
    }

    /** @test */
    public function it_cannot_add_same_option_twice()
    {
        $product = $this->createdProduct();

        $product->updateOptions([Option::create($product->productId, OptionId::fromString('ooo'), [])]);
        $product->updateOptions([Option::create($product->productId, OptionId::fromString('ooo'), [])]);

        $this->assertCount(1, $product->getChildEntities()[Option::class]);
    }

    /** @test */
    public function it_can_update_option_values()
    {
        $product = $this->createdProduct();

        $product->updateOptions([$option = Option::create($product->productId, OptionId::fromString('ooo'),[])]);
        $option->updateOptionValues([OptionValue::create($option->optionId, OptionValueId::fromString('xxx'), [
            'label' => [
                'nl' => 'option value label nl',
                'en' => 'option value label en',
            ],
        ])]);

        $this->assertEquals([
            new ProductCreated(ProductId::fromString('xxx')),
            new OptionsUpdated(ProductId::fromString('xxx')),
        ], $product->releaseEvents());

        $this->assertEquals([
            [
                'product_id' => $product->productId->get(),
                'option_id' => 'ooo',
                'values' => [
                    [
                        'option_id' => 'ooo',
                        'option_value_id' => 'xxx',
                        'data' => json_encode([
                            'label' => [
                                'nl' => 'option value label nl',
                                'en' => 'option value label en',
                            ],
                        ])
                    ]
                ],
                'data' => json_encode([]),
            ]
        ], $product->getChildEntities()[Option::class]);
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
    public function it_can_update_variant()
    {
        $product = $this->createdProductWithVariant();

        $variant = $product->getVariants()[0];
        $variant->updatePrice(VariantUnitPrice::zero(), VariantSalePrice::zero());

        $product->updateVariant($variant);

        $this->assertEquals('0', $product->getChildEntities()[Variant::class][0]['unit_price']);
        $this->assertEquals('0', $product->getChildEntities()[Variant::class][0]['sale_price']);
        $this->assertEquals([
            'option-value-id'
        ], $product->getVariants()[0]->getMappedData()['option_value_ids']);
    }

    /** @test */
    public function it_can_delete_variant()
    {
        $product = $this->createdProductWithVariant();

        $product->deleteVariant(VariantId::fromString('yyy'));

        $this->assertEquals([
            new ProductCreated(ProductId::fromString('xxx')),
            new VariantCreated(ProductId::fromString('xxx'), VariantId::fromString('yyy')),
            new VariantDeleted(ProductId::fromString('xxx'), VariantId::fromString('yyy')),
        ], $product->releaseEvents());
    }

    /** @test */
    public function it_can_get_all_options()
    {
        $product = $this->createdProductWithOptions();

        /** @var Option[] $options */
        $options = $product->getOptions();

        $this->assertCount(2, $options);
        $this->assertCount(2, $options['ooo']->getOptionValues());
        $this->assertCount(1, $options['ppp']->getOptionValues());

        $this->assertEquals('xxx', $options['ooo']->getOptionValues()[0]->optionValueId);
        $this->assertEquals('yyy', $options['ooo']->getOptionValues()[1]->optionValueId);
        $this->assertEquals('zzz', $options['ppp']->getOptionValues()[0]->optionValueId);
    }
}
