<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

final class VariantRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider variants
     */
    public function it_can_save_and_find_an_variant(Product $product, Variant $variant)
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->productRepositories()[$i]->save($product);
            $repository->save($variant);

            $variantStates = $repository->getStatesByProduct($variant->productId);
            $this->assertEquals([$variant], array_map(fn($variantState) => Variant::fromMappedData($variantState, ['product_id' => 'xxx']), $variantStates));
        }
    }

    /**
     * @test
     * @dataProvider variants
     */
    public function it_can_sync_option_values(Product $product, Variant $variant)
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->productRepositories()[$i]->save($product);
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
    public function it_can_delete_an_variant(Product $product, Variant $variant)
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->productRepositories()[$i]->save($product);
            $repository->save($variant);
            $repository->delete($variant->variantId);

            $this->assertCount(0, $repository->getStatesByProduct($variant->productId));
        }
    }

    public function test_it_can_generate_a_next_reference()
    {
        foreach ($this->repositories() as $repository) {
            $this->assertInstanceOf(VariantId::class, $repository->nextReference());
        }
    }

    /**
     * @test
     * @dataProvider variants
     */
    public function it_can_find_variant_for_cart(Product $product, Variant $variant)
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->productRepositories()[$i]->save($product);

            $this->assertNotNull($repository->findVariantForCart($variant->variantId));
        }
    }

    /**
     * @test
     * @dataProvider variants
     */
    public function it_can_find_all_variants_for_cart(Product $product, Variant $variant)
    {
        foreach ($this->repositories() as $i => $repository) {
            $this->productRepositories()[$i]->save($product);

            $this->assertNotNull($repository->findAllVariantsForCart([$variant->variantId]));
        }
    }

    private function repositories(): \Generator
    {
        yield new InMemoryVariantRepository();
        yield new MysqlVariantRepository(new TestContainer());
    }

    private function productRepositories(): array
    {
        return [
            new InMemoryProductRepository(),
            new MysqlProductRepository(new MysqlVariantRepository(new TestContainer())),
        ];
    }

    public function variants(): \Generator
    {
        $product = $this->createProductWithVariant();

        yield [
            $product,
            $product->getVariants()[0],
        ];

        $product = $this->createProductWithPersonalisations();

        yield [
            $product,
            $product->getVariants()[0],
        ];
    }
}
