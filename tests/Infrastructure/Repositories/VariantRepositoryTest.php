<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindVariant;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;

final class VariantRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider variants
     */
    public function it_can_save_and_find_an_variant(Variant $variant)
    {
        foreach($this->repositories() as $repository) {
            $repository->save($variant);

            $variantStates = $repository->getStatesByProduct($variant->productId);
            $this->assertEquals([$variant], array_map(fn($variantState) => Variant::fromMappedData($variantState, ['product_id' => 'xxx']), $variantStates));
        }
    }

    /**
     * @test
     * @dataProvider variants
     */
    public function it_can_sync_option_values(Variant $variant)
    {
        foreach($this->repositories() as $repository) {
            $repository->save($variant);

            // Resave so that the sync check occurs
            $repository->save($variant);

            $this->assertEquals($variant->getMappedData()['option_value_ids'], $repository->getStatesByProduct($variant->productId)[0]['option_value_ids']);
        }
    }

    /**
     * @test
     * @dataProvider variants
     */
    public function it_can_delete_an_variant(Variant $variant)
    {
        foreach($this->repositories() as $repository) {
            $repository->save($variant);
            $repository->delete($variant->variantId);

            $this->assertCount(0, $repository->getStatesByProduct($variant->productId));
        }
    }

    /** @test */
    public function it_can_generate_a_next_reference()
    {
        foreach($this->repositories() as $repository) {
            $this->assertInstanceOf(VariantId::class, $repository->nextReference());
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryVariantRepository(new InMemoryProductRepository());
        yield new MysqlVariantRepository();
    }

    public function variants(): \Generator
    {
        yield [$this->createdVariant()];
        yield [$this->createdVariantWithOption()];
    }
}
