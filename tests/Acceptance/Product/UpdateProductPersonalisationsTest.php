<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductPersonalisations;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationType;

class UpdateProductPersonalisationsTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_add_personalisations()
    {
        $productId = $this->createAProduct('50', ['1', '2'], 'sku', ['title' => ['nl' => 'foobar nl']]);

        $dataPayload = [
            'label' => ['nl' => 'label nl'],
            'custom' => 'foobar',
        ];

        $this->productApplication->updateProductPersonalisations(new UpdateProductPersonalisations($productId->get(), [[
            'personalisation_id' => null,
            'personalisation_type' => PersonalisationType::TEXT,
            'data' => $dataPayload,
        ]]));

        $product = $this->productRepository->find($productId);

        $this->assertArrayEqualsWithWildcard([
            [
                'product_id' => '*',
                'personalisation_id' => '*',
                'personalisation_type' => PersonalisationType::TEXT,
                'data' => json_encode($dataPayload),
            ],
        ], $product->getChildEntities()[Personalisation::class]);
    }

    public function test_it_can_update_existing_personalisations()
    {
        $productId = $this->createAProduct('50', ['1', '2'], 'sku', ['title' => ['nl' => 'foobar nl']]);

        $dataPayload = [
            'label' => ['nl' => 'label nl'],
            'custom' => 'foobar',
        ];

        $this->productApplication->updateProductPersonalisations(new UpdateProductPersonalisations($productId->get(), [[
            'personalisation_id' => null,
            'personalisation_type' => PersonalisationType::TEXT,
            'data' => $dataPayload,
        ]]));

        $product = $this->productRepository->find($productId);

        $personalisation_id = $product->getChildEntities()[Personalisation::class][0]['personalisation_id'];

        // Update
        $this->productApplication->updateProductPersonalisations(new UpdateProductPersonalisations($productId->get(), [[
            'personalisation_id' => $personalisation_id,
            'personalisation_type' => PersonalisationType::IMAGE,
            'data' => $dataPayload,
        ]]));

        $this->assertArrayEqualsWithWildcard([
            [
                'product_id' => '*',
                'personalisation_id' => $personalisation_id,
                'personalisation_type' => PersonalisationType::IMAGE,
                'data' => json_encode($dataPayload),
            ],
        ], $product->getChildEntities()[Personalisation::class]);
    }

    public function test_it_can_remove_existing_personalisations()
    {
        $product = $this->createProductWithPersonalisations();
        $this->productRepository->save($product);

        $this->productApplication->updateProductPersonalisations(new UpdateProductPersonalisations($product->productId->get(), []));

        $product = $this->productRepository->find($product->productId);

        $this->assertEquals([], $product->getChildEntities()[Personalisation::class]);
    }

    public function test_it_can_reorder_personalisations()
    {
    }
}
