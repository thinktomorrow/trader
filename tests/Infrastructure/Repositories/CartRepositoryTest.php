<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisation;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationType;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlCartRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryCartRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryOrderRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

final class CartRepositoryTest extends TestCase
{
    use RefreshDatabase;
    use PrepareWorld;

    /**
     * @test
     */
    public function it_can_find_a_cart()
    {
        $order = $this->createDefaultOrder();

        foreach ($this->orderRepositories() as $i => $orderRepository) {
            $this->prepareWorldForOrder($i);

            $orderRepository->save($order);

            $cartRepository = iterator_to_array($this->cartRepositories())[$i];

            $cart = $cartRepository->findCart($order->orderId);

            $this->assertInstanceOf(Cart::class, $cart);
            $this->assertCount(1, $cart->getLines());
            $this->assertEquals(
                Cash::from($order->getTotal()->getIncludingVat())->toLocalizedFormat(Locale::fromString('nl', 'BE')),
                $cart->getTotalPrice()
            );
        }
    }

    /** @test */
    public function it_can_check_if_cart_exists()
    {
        $order = $this->createDefaultOrder();
        $order->updateState(DefaultOrderState::cart_pending);

        foreach ($this->orderRepositories() as $i => $orderRepository) {
            $this->prepareWorldForOrder($i);

            $orderRepository->save($order);

            $cartRepository = iterator_to_array($this->cartRepositories())[$i];

            $this->assertTrue($cartRepository->existsCart($order->orderId));
        }
    }

    /** @test */
    public function it_can_save_line_personalisations()
    {
        $order = $this->createDefaultOrder();

        $line = $order->getLines()[0];
        $order->updateLinePersonalisations($line->lineId, [
            LinePersonalisation::create(
                $line->lineId,
                LinePersonalisationId::fromString('xxx'),
                PersonalisationId::fromString('ooo'),
                PersonalisationType::fromString('text'),
                'foobar',
                ['foo' => 'bar']
            ),
        ]);

        foreach ($this->orderRepositories() as $i => $orderRepository) {
            $this->prepareWorldForOrder($i);

            $orderRepository->save($order);

            $cartRepository = iterator_to_array($this->cartRepositories())[$i];

            $cartLine = $cartRepository->findCart($order->orderId)->getLines()[0];
            $this->assertCount(1, $cartLine->getPersonalisations());
        }
    }

    /** @test */
    public function it_checks_if_cart_is_in_customer_hands()
    {
        $order = $this->createDefaultOrder();
        $order->updateState(DefaultOrderState::confirmed);

        foreach ($this->orderRepositories() as $i => $orderRepository) {
            $this->prepareWorldForOrder($i);

            $orderRepository->save($order);

            $cartRepository = iterator_to_array($this->cartRepositories())[$i];

            $this->assertFalse($cartRepository->existsCart($order->orderId));
        }
    }

    /** @test */
    public function it_should_not_find_an_order_no_longer_in_customer_hands()
    {
        $calls = 0;

        $order = $this->createDefaultOrder();
        $order->updateState(DefaultOrderState::confirmed);

        foreach ($this->orderRepositories() as $i => $orderRepository) {
            $this->prepareWorldForOrder($i);

            $orderRepository->save($order);

            $cartRepository = iterator_to_array($this->cartRepositories())[$i];

            try {
                $cartRepository->findCart($order->orderId);
            } catch (\DomainException $e) {
                $calls++;
            }
        }

        $this->assertEquals(2, $calls);
    }

    private function orderRepositories(): \Generator
    {
        yield new InMemoryOrderRepository();
        yield (new TestContainer())->get(MysqlOrderRepository::class);
    }

    private function cartRepositories(): \Generator
    {
        yield new InMemoryCartRepository();
        yield new MysqlCartRepository(new TestContainer(), (new TestContainer())->get(MysqlOrderRepository::class));
    }
}
