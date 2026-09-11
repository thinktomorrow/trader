<?php

declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\Personalisations\PersonalisationField;
use Thinktomorrow\Trader\Application\Product\Personalisations\PersonalisationFieldsComposer;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductPersonalisations;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationType;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultPersonalisationField;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class UpdateProductPersonalisationsTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_compose_fields_from_a_loaded_product_without_reloading_it(): void
    {
        $product = $this->catalogContext->createProduct();
        $personalisation = $this->catalogContext->makePersonalisation();
        $this->catalogContext->addPersonalisationToProduct($product, $personalisation);
        $container = new TestContainer;
        $container->add(PersonalisationField::class, DefaultPersonalisationField::from($personalisation));
        $repository = $this->catalogContext->repos()->productRepository();
        $composer = new PersonalisationFieldsComposer($repository, $container);
        $locale = Locale::fromString('fr');
        $expected = $composer->get($product->productId, $locale);
        $repository->delete($product->productId);

        $fields = $composer->getForProduct($product, $locale);

        $this->assertCount(1, $fields);
        $this->assertEquals($expected, $fields);
        $this->assertSame($personalisation->personalisationId->get(), $fields[0]->getPersonalisationId());
    }

    public function test_it_can_compose_empty_fields_from_a_loaded_product(): void
    {
        $product = $this->catalogContext->createProduct();
        $repository = $this->catalogContext->repos()->productRepository();
        $repository->delete($product->productId);
        $composer = new PersonalisationFieldsComposer($repository, new TestContainer);

        $this->assertSame([], $composer->getForProduct($product, Locale::fromString('nl')));
    }

    public function test_it_can_add_personalisations()
    {
        $product = $this->catalogContext->createProduct();
        $productId = $product->productId;

        $dataPayload = [
            'label' => ['nl' => 'label nl'],
            'custom' => 'foobar',
        ];

        $this->catalogContext->apps()->productApplication()->updateProductPersonalisations(new UpdateProductPersonalisations($productId->get(), [[
            'personalisation_id' => null,
            'personalisation_type' => PersonalisationType::TEXT,
            'data' => $dataPayload,
        ]]));

        $product = $this->catalogContext->repos()->productRepository()->find($productId);

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
        $product = $this->catalogContext->createProduct();
        $productId = $product->productId;

        $dataPayload = [
            'label' => ['nl' => 'label nl'],
            'custom' => 'foobar',
        ];

        $this->catalogContext->apps()->productApplication()->updateProductPersonalisations(new UpdateProductPersonalisations($productId->get(), [[
            'personalisation_id' => null,
            'personalisation_type' => PersonalisationType::TEXT,
            'data' => $dataPayload,
        ]]));

        $product = $this->catalogContext->repos()->productRepository()->find($productId);

        $personalisation_id = $product->getChildEntities()[Personalisation::class][0]['personalisation_id'];

        // Update
        $this->catalogContext->apps()->productApplication()->updateProductPersonalisations(new UpdateProductPersonalisations($productId->get(), [[
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
        $product = $this->catalogContext->createProduct();
        $personalisation = $this->catalogContext->makePersonalisation();
        $this->catalogContext->addPersonalisationToProduct($product, $personalisation);

        $this->catalogContext->apps()->productApplication()->updateProductPersonalisations(new UpdateProductPersonalisations($product->productId->get(), []));

        $product = $this->catalogContext->repos()->productRepository()->find($product->productId);

        $this->assertEquals([], $product->getChildEntities()[Personalisation::class]);
    }
}
