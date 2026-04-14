<?php

declare(strict_types=1);

namespace Tests\Unit\Model\Product;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationType;

class ProductPersonalisationTest extends TestCase
{
    public function test_it_can_add_personalisation()
    {
        $product = $this->catalogContext->createProduct();

        $product->updatePersonalisations([Personalisation::create($product->productId, PersonalisationId::fromString('ooo'), PersonalisationType::fromString(PersonalisationType::TEXT), ['foo' => 'bar'])]);

        $this->assertEquals([
            [
                'product_id' => $product->productId->get(),
                'personalisation_id' => 'ooo',
                'personalisation_type' => PersonalisationType::TEXT,
                'data' => json_encode(['foo' => 'bar']),
            ],
        ], $product->getChildEntities()[Personalisation::class]);
    }

    public function test_it_cannot_add_same_personalisation_twice()
    {
        $product = $this->catalogContext->createProduct();

        $product->updatePersonalisations([Personalisation::create($product->productId, PersonalisationId::fromString('ooo'), PersonalisationType::fromString(PersonalisationType::TEXT), [])]);
        $product->updatePersonalisations([Personalisation::create($product->productId, PersonalisationId::fromString('ooo'), PersonalisationType::fromString(PersonalisationType::TEXT), [])]);

        $this->assertCount(1, $product->getChildEntities()[Personalisation::class]);
    }

    public function test_it_can_update_personalisation_values()
    {
        $product = $this->catalogContext->createProduct();

        $product->updatePersonalisations([$personalisation = Personalisation::create($product->productId, PersonalisationId::fromString('ooo'), PersonalisationType::fromString(PersonalisationType::TEXT), [])]);

        $this->assertEquals([
            [
                'product_id' => $product->productId->get(),
                'personalisation_id' => 'ooo',
                'personalisation_type' => PersonalisationType::TEXT,
                'data' => json_encode([]),
            ],
        ], $product->getChildEntities()[Personalisation::class]);
    }

    public function test_it_can_rearrange_personalisations()
    {
        $product = $this->catalogContext->createProduct();
        $personalisation = $this->catalogContext->makePersonalisation();
        $personalisation2 = $this->catalogContext->makePersonalisation('product-aaa', 'personalisation-bbb');
        $this->catalogContext->addPersonalisationToProduct($product, $personalisation);
        $this->catalogContext->addPersonalisationToProduct($product, $personalisation2);

        // Switch order
        $product->updatePersonalisations([$personalisation2, $personalisation]);

        $this->assertEquals([
            $personalisation2->getMappedData(),
            $personalisation->getMappedData(),
        ], $product->getChildEntities()[Personalisation::class]);
    }

    public function test_it_can_get_all_personalisations()
    {
        $product = $this->catalogContext->createProduct();
        $personalisation = $this->catalogContext->makePersonalisation();
        $this->catalogContext->addPersonalisationToProduct($product, $personalisation);

        $this->assertCount(1, $product->getPersonalisations());
    }

    public function test_it_rejects_invalid_personalisations()
    {
        $product = $this->catalogContext->createProduct();

        $this->expectException(\InvalidArgumentException::class);

        $product->updatePersonalisations([new \stdClass]);
    }
}
