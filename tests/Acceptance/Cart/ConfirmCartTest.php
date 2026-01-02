<?php
declare(strict_types=1);

namespace Tests\Acceptance\Cart;

use Thinktomorrow\Trader\Application\Cart\CompleteCart;
use Thinktomorrow\Trader\Application\Cart\ConfirmCart;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStateUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\OrderAlreadyInMerchantHands;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;

class ConfirmCartTest extends CartContext
{
    public function test_it_can_complete_a_cart()
    {
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-variant-aaa', 2);

        $this->orderContext->orderApps()->customerApplication()->completeCart(new CompleteCart('xxx'));

        $this->assertEquals(
            new OrderStateUpdated(OrderId::fromString('xxx'), DefaultOrderState::cart_pending, DefaultOrderState::cart_completed),
            last($this->eventDispatcher->releaseDispatchedEvents())
        );

        $order = $this->orderContext->orderRepos()->orderRepository()->find(OrderId::fromString('xxx'));
        $this->assertSame(DefaultOrderState::cart_completed, $order->getOrderState());
    }

    public function test_it_can_confirm_a_cart()
    {
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-variant-aaa', 2);

        $this->orderContext->orderApps()->customerApplication()->confirmCart(new ConfirmCart('xxx'));

        $this->assertEquals(
            new OrderStateUpdated(OrderId::fromString('xxx'), DefaultOrderState::cart_pending, DefaultOrderState::confirmed),
            last($this->eventDispatcher->releaseDispatchedEvents())
        );

        $order = $this->orderContext->orderRepos()->orderRepository()->find(OrderId::fromString('xxx'));
        $this->assertSame(DefaultOrderState::confirmed, $order->getOrderState());
    }

    public function test_it_can_confirm_a_completed_cart()
    {
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-variant-aaa', 2);

        $this->orderContext->orderApps()->customerApplication()->completeCart(new CompleteCart('xxx'));
        $this->orderContext->orderApps()->customerApplication()->confirmCart(new ConfirmCart('xxx'));

        $this->assertEquals(
            new OrderStateUpdated(OrderId::fromString('xxx'), DefaultOrderState::cart_completed, DefaultOrderState::confirmed),
            last($this->eventDispatcher->releaseDispatchedEvents())
        );

        $order = $this->orderContext->orderRepos()->orderRepository()->find(OrderId::fromString('xxx'));
        $this->assertSame(DefaultOrderState::confirmed, $order->getOrderState());
    }

    public function test_it_cannot_confirm_a_cart_when_state_not_allows_it()
    {
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-variant-aaa', 2);

        $this->orderContext->orderApps()->customerApplication()->confirmCart(new ConfirmCart('xxx'));

        $this->expectException(OrderAlreadyInMerchantHands::class);
        $this->orderContext->orderApps()->customerApplication()->confirmCart(new ConfirmCart('xxx'));
    }

    public function test_a_confirmed_cart_is_no_longer_retrievable_via_cart_repo()
    {
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-variant-aaa', 2);

        $this->orderContext->orderApps()->customerApplication()->confirmCart(new ConfirmCart('xxx'));

        // Cart is no longer retrievable since it is in merchant hands
        $this->expectException(OrderAlreadyInMerchantHands::class);
        $this->orderContext->orderRepos()->cartRepository()->findCart(OrderId::fromString('xxx'));
    }
}
