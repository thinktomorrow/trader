<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductOptions;
use Thinktomorrow\Trader\Domain\Model\Product\Events\OptionsUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;

class UpdateProductOptionsTest extends ProductContext
{
    use TestHelpers;

    /** @test */
    public function it_can_add_options()
    {
        $productId = $this->createAProduct('50', ['1','2'], 'sku', ['title' => ['nl' => 'foobar nl']]);

        $dataPayload = [
            'label' => ['nl' => 'label nl'],
            'custom' => 'foobar',
        ];

        $this->productApplication->updateProductOptions(new UpdateProductOptions($productId->get(), [[
            'option_id' => null,
            'data' => $dataPayload,
            'values' => [
                [
                    'option_value_id' => null,
                    'data' => $dataPayload,
                ],

            ],
        ]]));

        $product = $this->productRepository->find($productId);

        $this->assertArrayEqualsWithWildcard([
           [
               'product_id' => '*',
               'option_id' => '*',
               'data' => json_encode($dataPayload),
               'values' => [
                    [
                        'option_id' => '*',
                        'option_value_id' => '*',
                        'data' => json_encode($dataPayload),
                    ],
               ],
           ],
        ], $product->getChildEntities()[Option::class]);

        $this->assertEquals([
            new OptionsUpdated($productId),
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }

    /** @test */
    public function it_can_update_existing_options()
    {
        $productId = $this->createAProduct('50', ['1','2'], 'sku', ['title' => ['nl' => 'foobar nl']]);

        $dataPayload = [
            'label' => ['nl' => 'label nl'],
            'custom' => 'foobar',
        ];

        $this->productApplication->updateProductOptions(new UpdateProductOptions($productId->get(), [[
            'option_id' => null,
            'data' => $dataPayload,
            'values' => [
                [
                    'option_value_id' => null,
                    'data' => $dataPayload,
                ],

            ],
        ]]));

        $product = $this->productRepository->find($productId);

        $this->assertEquals([
            new OptionsUpdated($productId),
        ], $this->eventDispatcher->releaseDispatchedEvents());

        $option_id = $product->getChildEntities()[Option::class][0]['option_id'];
        $option_value_id = $product->getChildEntities()[Option::class][0]['values'][0]['option_value_id'];

        // Update (+ add a second value)
        $this->productApplication->updateProductOptions(new UpdateProductOptions($productId->get(), [[
            'option_id' => $option_id,
            'data' => $dataPayload,
            'values' => [
                [
                    'option_value_id' => $option_value_id,
                    'data' => $dataPayload,
                ],
                [
                    'option_value_id' => null,
                    'data' => array_merge($dataPayload, ['foo' => 'bar']),
                ],

            ],
        ]]));

        $this->assertArrayEqualsWithWildcard([
            [
                'product_id' => '*',
                'option_id' => $option_id,
                'data' => json_encode($dataPayload),
                'values' => [
                    [
                        'option_id' => $option_id,
                        'option_value_id' => $option_value_id,
                        'data' => json_encode($dataPayload),
                    ],
                    [
                        'option_id' => $option_id,
                        'option_value_id' => '*',
                        'data' => json_encode(array_merge($dataPayload, ['foo' => 'bar'])),
                    ],
                ],
            ],
        ], $product->getChildEntities()[Option::class]);

        $this->assertEquals([
            new OptionsUpdated($productId),
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }

    /** @test */
    public function it_can_remove_existing_options()
    {
        $product = $this->createdProductWithOptions();
        $this->productRepository->save($product);

        $this->productApplication->updateProductOptions(new UpdateProductOptions($product->productId->get(), []));

        $product = $this->productRepository->find($product->productId);

        $this->assertEquals([], $product->getChildEntities()[Option::class]);
    }

    /** @test */
    public function it_can_remove_existing_option_values()
    {
        $productId = $this->createAProduct('50', ['1','2'], 'sku', ['title' => ['nl' => 'foobar nl']]);

        $dataPayload = [
            'label' => ['nl' => 'label nl'],
            'custom' => 'foobar',
        ];

        $this->productApplication->updateProductOptions(new UpdateProductOptions($productId->get(), [[
            'option_id' => null,
            'data' => $dataPayload,
            'values' => [
                [
                    'option_value_id' => null,
                    'data' => $dataPayload,
                ],

            ],
        ]]));

        $product = $this->productRepository->find($productId);

        $option_id = $product->getChildEntities()[Option::class][0]['option_id'];

        $this->productApplication->updateProductOptions(new UpdateProductOptions($productId->get(), [[
            'option_id' => $option_id,
            'data' => [],
            'values' => [],
        ]]));

        $product = $this->productRepository->find($productId);

        $this->assertArrayEqualsWithWildcard([
            [
                'product_id' => '*',
                'option_id' => $option_id,
                'data' => json_encode([]),
                'values' => [],
            ],
        ], $product->getChildEntities()[Option::class]);
    }

    /** @test */
    public function it_can_reorder_options()
    {
    }
}
