<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories\ProductOptions;

use Tests\Infrastructure\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVariantRepository;
use Thinktomorrow\Trader\Application\Product\GetProductOptions\VariantProductOptionsRepository;

final class VariantProductOptionsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_all_variants()
    {
        $product = $this->createdProductWithOptions();

        /** @var VariantProductOptionsRepository $repository */
        foreach($this->repositories() as $repositories) {
            $productRepository = $repositories[0];
            $variantRepository = $repositories[1];

            $productRepository->save($product);

            $variants = $variantRepository->getVariantProductOptions($product->productId);
            $this->assertCount(1, $variants);
            $this->assertCount(2, $variants[0]->getOptions());
        }
    }

    private function repositories(): \Generator
    {
        yield [
            new InMemoryProductRepository(),
            new InMemoryVariantRepository(new InMemoryProductRepository()),
        ];

        yield [
            new MysqlProductRepository(new MysqlVariantRepository()),
            new MysqlVariantRepository(),
        ];
    }
}
