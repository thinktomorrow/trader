<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Event\OptionsUpdated;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductOptions;

class UpdateProductOptionsTest extends ProductContext
{
    use TestHelpers;

    /** @test */
    public function it_can_add_options()
    {
        $productId = $this->createAProduct('50', ['1','2'], ['title' => ['nl' => 'foobar nl']]);

        $dataPayload = [
            'label' => ['nl' => 'label nl'],
            'custom' => 'foobar',
        ];

        $this->productApplication->updateProductOptions(new UpdateProductOptions($productId->get(), [[
            'id' => null,
            'values' => [
                [
                    'id' => null,
                    'data' => $dataPayload
                ]

            ],
        ]]));

        $product = $this->productRepository->find($productId);

        $this->assertArrayEqualsWithWildcard([
           [
               'product_id' => '*',
               'option_id' => '*',
               'values' => [
                    [
                        'option_id' => '*',
                        'option_value_id' => '*',
                        'data' => json_encode($dataPayload),
                    ]
               ],
           ]
        ], $product->getChildEntities()[Option::class]);

        $this->assertEquals([
            new OptionsUpdated($productId),
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }

    /** @test */
    public function it_can_update_existing_options()
    {
        $productId = $this->createAProduct('50', ['1','2'], ['title' => ['nl' => 'foobar nl']]);

        $dataPayload = [
            'label' => ['nl' => 'label nl'],
            'custom' => 'foobar',
        ];

        $this->productApplication->updateProductOptions(new UpdateProductOptions($productId->get(), [[
            'id' => null,
            'values' => [
                [
                    'id' => null,
                    'data' => $dataPayload
                ]

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
            'id' => $option_id,
            'values' => [
                [
                    'id' => $option_value_id,
                    'data' => $dataPayload
                ],
                [
                    'id' => null,
                    'data' => array_merge($dataPayload, ['foo' => 'bar'])
                ]

            ],
        ]]));

        $this->assertArrayEqualsWithWildcard([
            [
                'product_id' => '*',
                'option_id' => $option_id,
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
                    ]
                ],
            ]
        ], $product->getChildEntities()[Option::class]);

        $this->assertEquals([
            new OptionsUpdated($productId),
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }

    /** @test */
    public function it_can_remove_existing_options()
    {
        $productId = $this->createAProduct('50', ['1','2'], ['title' => ['nl' => 'foobar nl']]);

        $dataPayload = [
            'label' => ['nl' => 'label nl'],
            'custom' => 'foobar',
        ];

        $this->productApplication->updateProductOptions(new UpdateProductOptions($productId->get(), [[
            'id' => null,
            'values' => [
                [
                    'id' => null,
                    'data' => $dataPayload
                ]

            ],
        ]]));

        $product = $this->productRepository->find($productId);

        $option_id = $product->getChildEntities()[Option::class][0]['option_id'];

        $this->productApplication->updateProductOptions(new UpdateProductOptions($productId->get(), [[
            'id' => $option_id,
            'values' => [],
        ]]));

        $this->assertArrayEqualsWithWildcard([
            [
                'product_id' => '*',
                'option_id' => $option_id,
                'values' => [],
            ]
        ], $product->getChildEntities()[Option::class]);
    }
}
