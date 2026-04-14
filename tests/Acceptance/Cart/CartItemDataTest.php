<?php

declare(strict_types=1);

namespace Tests\Acceptance\Cart;

class CartItemDataTest extends CartContext
{
    public function test_as_a_visitor_i_need_to_be_able_to_add_data_to_a_cart_item()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-variant-aaa', 1, ['foo' => 'bar']);
        $this->thenIShouldHaveProductInTheCart(1, 1);
        $this->thenTheCartItemShouldContainData('lightsaber-variant-aaa', 'foo', 'bar');
    }

    public function test_as_a_visitor_i_need_to_be_able_to_add_data_to_a_cart_item_while_other_data_is_left_untouched()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-variant-aaa', 1, ['foo' => 'bar']);

        $this->thenIShouldHaveProductInTheCart(1, 1);

        $this->thenTheCartItemShouldContainData('lightsaber-variant-aaa', 'foo', 'bar');
    }
}
