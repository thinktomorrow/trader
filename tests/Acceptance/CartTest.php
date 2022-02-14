<?php
declare(strict_types=1);

namespace Tests\Acceptance;

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
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->givenShippingCostsForAPurchaseOfEur(2, 0, 10);
        $this->whenIAddTheProductToTheCart('lightsaber', 1);
        $this->thenIShouldHaveProductInTheCart(1, 1);
        $this->thenTheOverallCartPriceShouldBeEur(7);
    }
}
