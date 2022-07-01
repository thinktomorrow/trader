<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderState;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCartRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCartRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

final class CartRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider orders
     */
    public function it_can_find_a_cart(Order $order)
    {
        foreach ($this->orderRepositories() as $i => $orderRepository) {
            $orderRepository->save($order);

            $productRepository = iterator_to_array($this->productRepositories())[$i];
            $cartRepository = iterator_to_array($this->cartRepositories())[$i];

            // Make sure we have a purchasable variant
            $product = $this->createdProductWithVariant();
            $productRepository->save($product);

            $cart = $cartRepository->findCart($order->orderId);

            $this->assertInstanceOf(Cart::class, $cart);
            $this->assertCount(1, $cart->getLines());
            $this->assertEquals(
                Cash::from($order->getTotal()->getIncludingVat())->toLocalizedFormat(Locale::make('nl', 'BE')),
                $cart->getTotalPrice()
            );
        }
    }

    /** @test */
    public function it_should_not_find_an_order_no_longer_in_customer_hands()
    {
        $calls = 0;

        $order = $this->createdOrder();
        $order->updateState(OrderState::confirmed);

        foreach ($this->orderRepositories() as $i => $orderRepository) {

            $orderRepository->save($order);

            $cartRepository = iterator_to_array($this->cartRepositories())[$i];
            try{
                $cartRepository->findCart($order->orderId);
            } catch(\DomainException $e) {
                $calls++;
            }
        }

        $this->assertEquals(2, $calls);
    }

    /** @test */
    public function it_can_find_cart_without_variant_when_variant_is_no_longer_present()
    {
        // TODO: this should be detected by refresh job of the order. Triggered by variant
    }

    private function orderRepositories(): \Generator
    {
        yield new InMemoryOrderRepository();
        yield new MysqlOrderRepository();
    }

    private function cartRepositories(): \Generator
    {
        yield new InMemoryCartRepository();
        yield new MysqlCartRepository(new TestContainer(), new MysqlOrderRepository());
    }

    private function productRepositories(): \Generator
    {
        yield new InMemoryProductRepository();
        yield new MysqlProductRepository(new MysqlVariantRepository(new TestContainer()));
    }

    public function orders(): \Generator
    {
        yield [$this->createdOrder()];
    }
}
