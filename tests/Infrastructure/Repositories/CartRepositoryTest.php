<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisation;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationType;
use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;
use Thinktomorrow\Trader\Testing\Order\OrderContext;

final class CartRepositoryTest extends TestCase
{
    public function test_it_can_find_a_cart()
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $order = $orderContext->createDefaultOrder();

            $cart = $orderContext->findCart($order->orderId);

            $this->assertInstanceOf(Cart::class, $cart);
            $this->assertCount(2, $cart->getLines());
            $this->assertEquals(
                Cash::from($order->getTotalIncl())->toLocalizedFormat(Locale::fromString('nl', 'BE')),
                $cart->getFormattedTotalIncl()
            );
        }
    }

    public function test_it_can_check_if_cart_exists()
    {
        foreach (OrderContext::drivers() as $orderContext) {

            // Create cart pending order
            $order = $orderContext->createDefaultOrder();
            $order->updateState(DefaultOrderState::cart_pending);
            $orderContext->saveOrder($order);

            $repository = $orderContext->repos()->cartRepository();

            $this->assertTrue($repository->existsCart($order->orderId));
        }
    }

    public function test_it_can_save_line_personalisations()
    {
        /** @var OrderContext $orderContext */
        foreach (OrderContext::drivers() as $orderContext) {
            $order = $orderContext->createDefaultOrder();

            /** @var CatalogContext $catalogContext */
            $catalogContext = CatalogContext::driver($orderContext->driverName);
            $product = $catalogContext->createProduct();
            $personalisation = $catalogContext->makePersonalisation();
            $catalogContext->addPersonalisationToProduct($product, $personalisation);
            $catalogContext->saveProduct($product);

            // Add line personalisation to order
            $line = $order->getLines()[0];
            $order->updateLinePersonalisations($line->lineId, [
                LinePersonalisation::create(
                    $line->lineId,
                    LinePersonalisationId::fromString('line-personalisation-aaa'),
                    PersonalisationId::fromString('personalisation-aaa'),
                    PersonalisationType::fromString('text'),
                    'foobar',
                    ['foo' => 'bar']
                ),
            ]);
            $orderContext->saveOrder($order);

            $cart = $orderContext->findCart($order->orderId);

            $this->assertCount(1, $cart->getLines()[0]->getPersonalisations());
        }
    }

    public function test_it_checks_if_cart_is_in_customer_hands()
    {
        foreach (OrderContext::drivers() as $orderContext) {

            // Create cart pending order
            $order = $orderContext->createDefaultOrder();
            $order->updateState(DefaultOrderState::confirmed);
            $orderContext->saveOrder($order);

            $repository = $orderContext->repos()->cartRepository();

            $this->assertFalse($repository->existsCart($order->orderId));
        }
    }

    public function test_it_should_not_find_an_order_no_longer_in_customer_hands()
    {
        $calls = 0;

        foreach (OrderContext::drivers() as $orderContext) {

            // Create cart pending order
            $order = $orderContext->createDefaultOrder();
            $order->updateState(DefaultOrderState::confirmed);
            $orderContext->saveOrder($order);

            $repository = $orderContext->repos()->cartRepository();

            try {
                $repository->findCart($order->orderId);
            } catch (\DomainException $e) {
                $calls++;
            }
        }

        $this->assertEquals(count(OrderContext::drivers()), $calls);

    }
}
