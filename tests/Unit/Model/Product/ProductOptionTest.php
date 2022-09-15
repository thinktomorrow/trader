<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Product;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\Events\OptionsUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValue;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;

class ProductOptionTest extends TestCase
{
    /** @test */
    public function it_can_add_option()
    {
        $product = $this->createProduct();

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
            ],
        ], $product->getChildEntities()[Option::class]);
    }

    /** @test */
    public function it_cannot_add_same_option_twice()
    {
        $product = $this->createProduct();

        $product->updateOptions([Option::create($product->productId, OptionId::fromString('ooo'), [])]);
        $product->updateOptions([Option::create($product->productId, OptionId::fromString('ooo'), [])]);

        $this->assertCount(1, $product->getChildEntities()[Option::class]);
    }

    public function test_when_deleting_option_all_corresponding_variant_option_values_are_removed_as_well()
    {
        $product = $this->createdProductWithVariant();

        $this->assertCount(1, $product->getOptions());
        $this->assertCount(1, $product->getVariants()[0]->getOptionValueIds());

        $product->updateOptions([]);

        $this->assertCount(0, $product->getOptions());
        $this->assertCount(0, $product->getVariants()[0]->getOptionValueIds());
    }

    /** @test */
    public function it_can_update_option_values()
    {
        $product = $this->createProduct();

        $product->updateOptions([$option = Option::create($product->productId, OptionId::fromString('ooo'), [])]);
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
                        ]),
                    ],
                ],
                'data' => json_encode([]),
            ],
        ], $product->getChildEntities()[Option::class]);
    }

    /** @test */
    public function it_can_rearrange_options()
    {
        $product = $this->createdProductWithOptions();

        // Switch order
        $product->updateOptions([
            Option::create($product->productId, OptionId::fromString('ppp'), []),
            Option::create($product->productId, OptionId::fromString('ooo'), []),
        ]);

        $this->assertEquals([
            [
                'product_id' => $product->productId->get(),
                'option_id' => 'ppp',
                'values' => [],
                'data' => json_encode([]),
            ],
            [
                'product_id' => $product->productId->get(),
                'option_id' => 'ooo',
                'values' => [],
                'data' => json_encode([]),
            ],
        ], $product->getChildEntities()[Option::class]);
    }

    /** @test */
    public function it_can_rearrange_option_values()
    {
        $product = $this->createdProductWithOptions();

        $product->updateOptions([
            $option = Option::create($product->productId, OptionId::fromString('ooo'), ['foo' => 'bar']),
        ]);

        // Switch order
        $option->updateOptionValues([
            OptionValue::create($option->optionId, OptionValueId::fromString('yyy'), []),
            OptionValue::create($option->optionId, OptionValueId::fromString('xxx'), []),
        ]);

        $this->assertEquals([
            [
                'product_id' => $product->productId->get(),
                'option_id' => 'ooo',
                'values' => [
                    [
                        'option_id' => 'ooo',
                        'option_value_id' => 'yyy',
                        'data' => json_encode([]),
                    ],
                    [
                        'option_id' => 'ooo',
                        'option_value_id' => 'xxx',
                        'data' => json_encode([]),
                    ],
                ],
                'data' => json_encode(['foo' => 'bar']),
            ],
        ], $product->getChildEntities()[Option::class]);
    }

    /** @test */
    public function it_can_get_all_options()
    {
        $product = $this->createdProductWithOptions();

        /** @var Option[] $options */
        $options = $product->getOptions();

        $this->assertCount(2, $options);
        $this->assertCount(2, $options[0]->getOptionValues());
        $this->assertCount(1, $options[1]->getOptionValues());

        $this->assertEquals('xxx', $options[0]->getOptionValues()[0]->optionValueId);
        $this->assertEquals('yyy', $options[0]->getOptionValues()[1]->optionValueId);
        $this->assertEquals('zzz', $options[1]->getOptionValues()[0]->optionValueId);
    }
}
