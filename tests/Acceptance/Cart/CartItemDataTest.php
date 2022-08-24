<?php
declare(strict_types=1);

namespace Tests\Acceptance\Cart;

class CartItemDataTest extends CartContext
{
    /** @test */
    public function in_order_to_personalise_products_as_a_visitor__i_need_to_be_able_to_add_data_to_a_cart_item()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 1, ['foo' => 'bar']);
        $this->thenIShouldHaveProductInTheCart(1, 1);
        $this->thenTheCartItemShouldContainData('lightsaber-123', 'foo', 'bar');
    }

    /** @test */
    public function in_order_to_personalise_products_as_a_visitor__i_need_to_be_able_to_add_data_to_a_cart_item_while_other_data_is_left_untouched()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 1, ['foo' => 'bar']);

        $this->thenIShouldHaveProductInTheCart(1, 1);

        $this->thenTheCartItemShouldContainData('lightsaber-123', 'foo', 'bar');
    }
}
