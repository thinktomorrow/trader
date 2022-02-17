<?php
declare(strict_types=1);

namespace Tests\Acceptance;

use Thinktomorrow\Trader\Application\Cart\ChooseBillingAddress;
use Thinktomorrow\Trader\Application\Cart\ChooseShippingAddress;

class CartTest extends CartContext
{
    /** @test */
    public function in_order_to_buy_products_as_a_visitor_I_need_to_be_able_to_put_products_in_my_cart()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheProductToTheCart('lightsaber', 2);
        $this->thenIShouldHaveProductInTheCart(1, 2);
        $this->thenTheOverallCartPriceShouldBeEur(10);
    }

    /** @test */
    public function in_order_to_buy_multiple_products_as_a_visitor_I_need_to_be_able_to_put_multiple_products_in_my_cart()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->givenThereIsAProductWhichCostsEur('kenobi scarf', 7);

        $this->whenIAddTheProductToTheCart('lightsaber', 1);
        $this->thenIShouldHaveProductInTheCart(1, 1);
        $this->thenTheOverallCartPriceShouldBeEur(5);

        $this->whenIAddTheProductToTheCart('kenobi scarf', 1);
        $this->thenIShouldHaveProductInTheCart(2, 1);
        $this->thenTheOverallCartPriceShouldBeEur(12);
    }

    /** @test */
    public function shipping_cost_should_be_added_when_buying_under_ten_euro()
    {
        $this->givenOrderHasAShippingCountry('BE');
        $this->givenShippingCostsForAPurchaseOfEur(2, 0, 10);

        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheProductToTheCart('lightsaber', 1);
        $this->thenIShouldHaveProductInTheCart(1, 1);
        $this->thenTheOverallCartPriceShouldBeEur(7);
    }

    /** @test */
    public function shipping_address_can_be_added()
    {
        $address = ['NL', 'example', '12', 'bus 2', '1000', 'Amsterdam'];

        $this->cartApplication->chooseShippingAddress(new ChooseShippingAddress(
            $this->getOrder()->orderId->get(), ...$address
        ));

        $this->assertEquals($address, array_values($this->getOrder()->getShippingAddress()->toArray()));
    }

    /** @test */
    public function billing_address_can_be_added()
    {
        $address = ['NL', 'example', '12', 'bus 2', '1000', 'Amsterdam'];

        $this->cartApplication->chooseBillingAddress(new ChooseBillingAddress(
            $this->getOrder()->orderId->get(), ...$address
        ));

        $this->assertEquals($address, array_values($this->getOrder()->getBillingAddress()->toArray()));
    }
}
