<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories\ProductOptions;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Common\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Thinktomorrow\Trader\Domain\Model\Customer\Customer;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Application\Product\GetProductOptions\ProductOptionsRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductDetailRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductDetailRepository;
use Thinktomorrow\Trader\Application\Product\GetProductOptions\VariantProductOptionsRepository;

final class ProductOptionsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_all_product_options_of_a_product()
    {
        $product = $this->createdProductWithOption();

        /** @var ProductOptionsRepository $repository */
        foreach($this->repositories() as $repositories) {
            $productRepository = $repositories[0];
            $productOptionRepository = $repositories[1];

            $productRepository->save($product);

            $productOptions = $productOptionRepository->getProductOptions($product->productId);
            $this->assertCount(1, $productOptions);
        }
    }

    private function repositories(): \Generator
    {
        yield [
            new InMemoryProductRepository(),
            new InMemoryProductDetailRepository(),
        ];

        yield [
            new MysqlProductRepository(new MysqlVariantRepository()),
            new MysqlProductDetailRepository(new TestContainer()),
        ];
    }
}
