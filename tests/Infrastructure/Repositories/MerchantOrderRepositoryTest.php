<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlMerchantOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryMerchantOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class MerchantOrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_find_a_merchantorder()
    {
        $order = $this->createDefaultOrder();

        foreach ($this->orderRepositories() as $i => $orderRepository) {
            $orderRepository->save($order);

            $productRepository = iterator_to_array($this->productRepositories())[$i];
            $merchantOrderRepository = iterator_to_array($this->merchantOrderRepositories())[$i];

            // Make sure we have a purchasable variant
            $product = $this->createdProductWithVariant();
            $productRepository->save($product);

            $merchantOrder = $merchantOrderRepository->findMerchantOrder($order->orderId);

            $this->assertInstanceOf(MerchantOrder::class, $merchantOrder);
            $this->assertCount(1, $merchantOrder->getLines());
            $this->assertEquals(
                Cash::from($order->getTotal()->getIncludingVat())->toLocalizedFormat(Locale::make('nl', 'BE')),
                $merchantOrder->getTotalPrice()
            );
        }
    }

    public function test_it_can_find_a_merchantorder_by_reference()
    {
        $order = $this->createDefaultOrder();

        foreach ($this->orderRepositories() as $i => $orderRepository) {
            $orderRepository->save($order);

            $merchantOrderRepository = iterator_to_array($this->merchantOrderRepositories())[$i];
            $merchantOrder = $merchantOrderRepository->findMerchantOrderByReference($order->orderReference);

            $this->assertInstanceOf(MerchantOrder::class, $merchantOrder);
        }
    }

    /** @test */
    public function it_can_find_merchant_order_without_variant_when_variant_is_no_longer_present()
    {
        // TODO: this should be detected by refresh job of the order. Triggered by variant
    }

    private function orderRepositories(): \Generator
    {
        yield new InMemoryOrderRepository();
        yield new MysqlOrderRepository();
    }

    private function merchantOrderRepositories(): \Generator
    {
        yield new InMemoryMerchantOrderRepository();
        yield new MysqlMerchantOrderRepository(new TestContainer(), new MysqlOrderRepository());
    }

    private function productRepositories(): \Generator
    {
        yield new InMemoryProductRepository();
        yield new MysqlProductRepository(new MysqlVariantRepository(new TestContainer()));
    }
}
