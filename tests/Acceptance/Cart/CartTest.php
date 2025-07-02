<?php
declare(strict_types=1);

namespace Tests\Acceptance\Cart;

use Thinktomorrow\Trader\Application\Cart\ClearCheckoutData;
use Thinktomorrow\Trader\Application\Cart\UpdateBillingAddress;
use Thinktomorrow\Trader\Application\Cart\VerifyCartVatExemption;
use Thinktomorrow\Trader\Application\Cart\VerifyCartVatNumber;
use Thinktomorrow\Trader\Application\VatNumber\VatNumberValidation;
use Thinktomorrow\Trader\Domain\Model\VatNumber\VatNumberValidationState;

class CartTest extends CartContext
{
    public function test_in_order_to_buy_products_as_a_visitor__i_need_to_be_able_to_put_products_in_my_cart()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);
        $this->thenIShouldHaveProductInTheCart(1, 2);
        $this->thenTheOverallCartPriceShouldBeEur(10);
    }

    public function test_in_order_to_buy_a_product_as_a_visitor_the_order_is_created_when__i_add_a_first_item_in_my_cart()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheFirstVariantToTheCart('lightsaber-123', 1);
        $this->thenIShouldHaveProductInTheCart(1, 1, 'xxx-123');
    }

    public function test_in_order_to_buy_products_in_quantity_as_a_visitor__i_need_to_be_able_to_put_same_product_in_my_cart_multiple_times()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 1, ['foo' => 'bar']);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 1, ['fez' => 'bes']);

        $this->thenIShouldHaveProductInTheCart(2, 1);
    }

    public function test_in_order_to_choose_my_quantity_as_a_visitor__i_need_to_be_able_to_change_quantity_of_a_product()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);
        $this->thenIShouldHaveProductInTheCart(1, 2);

        $this->whenIChangeTheProductQuantity('lightsaber-123', 3);
        $this->thenIShouldHaveProductInTheCart(1, 3);
        $this->thenTheOverallCartPriceShouldBeEur(15);
    }

    public function test_in_order_to_be_in_control_as_a_visitor__i_need_to_be_able_to_remove_a_product()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);
        $this->thenIShouldHaveProductInTheCart(1, 2);

        $this->whenIRemoveTheLine('lightsaber-123');
        $this->thenIShouldHaveProductInTheCart(0, 0);
        $this->thenTheOverallCartPriceShouldBeEur(0);
    }

    public function test_in_order_to_buy_multiple_products_as_a_visitor__i_need_to_be_able_to_put_multiple_products_in_my_cart()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->givenThereIsAProductWhichCostsEur('kenobi scarf', 7);

        $this->whenIAddTheVariantToTheCart('lightsaber-123', 1);
        $this->thenIShouldHaveProductInTheCart(1, 1);
        $this->thenTheOverallCartPriceShouldBeEur(5);

        $this->whenIAddTheVariantToTheCart('kenobi scarf-123', 1);
        $this->thenIShouldHaveProductInTheCart(2, 1);
        $this->thenTheOverallCartPriceShouldBeEur(12);
    }

    public function test_shipping_cost_should_be_added_when_buying_under_ten_euro()
    {
        $this->givenOrderHasAShippingCountry('BE');
        $this->givenShippingCostsForAPurchaseOfEur(2, 0, 10);

        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 1);
        $this->thenIShouldHaveProductInTheCart(1, 1);

        $this->whenIChooseShipping('bpost_home');
        $this->thenIShouldHaveAShippingCost(2);
        $this->thenTheOverallCartPriceShouldBeEur(7);
    }

    public function test_shipping_address_can_be_added()
    {
        $this->whenIAddShippingAddress('NL', 'example 12', 'bus 2', '1000', 'Amsterdam');

        $this->assertEquals(['NL', 'example 12', 'bus 2', '1000', 'Amsterdam'], array_values($this->getOrder()->getShippingAddress()->getAddress()->toArray()));
    }

    public function test_billing_address_can_be_added()
    {
        $address = ['NL', 'example 12', 'bus 2', '1000', 'Amsterdam'];

        $this->cartApplication->updateBillingAddress(new UpdateBillingAddress(
            $this->getOrder()->orderId->get(),
            ...$address
        ));

        $this->assertEquals($address, array_values($this->getOrder()->getBillingAddress()->getAddress()->toArray()));
    }

    public function test_it_can_verify_vat_number(): void
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);
        $this->whenIAddBillingAddress('BE', 'example 13', 'bus 2', '1200', 'Brussel');
        $this->whenIEnterShopperDetails('ben@tt.be');

        $this->vatNumberValidator->setExpectedResult(new VatNumberValidation('BE', '0123456789', VatNumberValidationState::invalid, []));

        $this->cartApplication->verifyVatNumber(new VerifyCartVatNumber(
            $this->getOrder()->orderId->get(),
            '0123456789',
        ));

        $cart = $this->cartRepository->findCart($this->getOrder()->orderId);
        $shopper = $cart->getShopper();

        $this->assertEquals('0123456789', $shopper->getVatNumber());
        $this->assertEquals('BE', $shopper->getVatNumberCountry());
        $this->assertEquals(false, $shopper->isVatNumberValid());
        $this->assertEquals(VatNumberValidationState::invalid, $shopper->getVatNumberState());
    }

    public function test_it_can_verify_vat_exemption(): void
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);
        $this->whenIAddBillingAddress('NL', 'example 13', 'bus 2', '1200 BK', 'Brussel');
        $this->whenIEnterShopperDetails('ben@tt.be', true);

        //        $this->vatNumberValidator->setExpectedResult(new VatNumberValidation('NL', '0123456789', VatNumberValidationState::valid, []));

        $this->cartApplication->verifyCartVatExemption(new VerifyCartVatExemption(
            $this->getOrder()->orderId->get(),
        ));

        $cart = $this->cartRepository->findCart($this->getOrder()->orderId);

        $this->assertTrue($cart->isVatExempt());
    }

    public function test_it_does_not_have_vat_exemption_by_default(): void
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);

        $this->cartApplication->verifyCartVatExemption(new VerifyCartVatExemption(
            $this->getOrder()->orderId->get(),
        ));

        $cart = $this->cartRepository->findCart($this->getOrder()->orderId);

        $this->assertFalse($cart->isVatExempt());
    }

    public function test_it_can_remove_cart_details()
    {
        // Product selection
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);

        // Fill in an entire cart
        $this->givenOrderHasAShippingCountry('BE');
        $this->givenShippingCostsForAPurchaseOfEur(2, 0, 10);
        $this->givenPaymentMethod(10);
        $this->whenIChooseShipping('bpost_home');

        $this->whenIAddShippingAddress('NL', 'example 12', 'bus 2', '1000', 'Amsterdam');
        $this->whenIAddBillingAddress('BE', 'example 13', 'bus 2', '1200', 'Brussel');
        $this->whenIChoosePayment('visa');
        $this->whenIEnterShopperDetails('ben@tt.be');

        // Assert all is present
        $cart = $this->cartRepository->findCart($this->getOrder()->orderId);
        $this->assertCount(1, $cart->getLines());
        $this->assertNotNull($cart->getBillingAddress());
        $this->assertNotNull($cart->getShippingAddress());
        $this->assertNotNull($cart->getShopper());
        $this->assertNotNull($cart->getShipping());
        $this->assertNotNull($cart->getPayment());

        // Now clear it - Everything but the products should be removed
        $this->cartApplication->clearCheckoutData(new ClearCheckoutData($this->getOrder()->orderId->get()));

        $cart = $this->cartRepository->findCart($this->getOrder()->orderId);
        $this->assertCount(1, $cart->getLines());
        $this->assertNull($cart->getBillingAddress());
        $this->assertNull($cart->getShippingAddress());
        $this->assertNull($cart->getShopper());
        $this->assertNull($cart->getShipping());
        $this->assertNull($cart->getPayment());
    }
}
