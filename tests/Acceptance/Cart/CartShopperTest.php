<?php
declare(strict_types=1);

namespace Tests\Acceptance\Cart;

use Thinktomorrow\Trader\Application\Cart\Read\CartShopper;
use Thinktomorrow\Trader\Application\Customer\UpdateBillingAddress;
use Thinktomorrow\Trader\Application\Customer\UpdateShippingAddress;
use Thinktomorrow\Trader\Domain\Common\Address\Address;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

class CartShopperTest extends CartContext
{
    public function test_it_can_see_guest_shopper()
    {
        $this->whenIEnterShopperDetails('foo@example.com');

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));

        $this->assertInstanceOf(CartShopper::class, $cart->getShopper());
        $this->assertEquals('foo@example.com', $cart->getShopper()->getEmail());
        $this->assertFalse($cart->getShopper()->isBusiness());
        $this->assertTrue($cart->getShopper()->isGuest());
        $this->assertFalse($cart->getShopper()->isCustomer());
    }

    public function test_it_can_see_customer_shopper()
    {
        $this->givenACustomerExists('foo@example.com');
        $this->whenIChooseCustomer('foo@example.com');

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));

        $this->assertInstanceOf(CartShopper::class, $cart->getShopper());
        $this->assertEquals('foo@example.com', $cart->getShopper()->getEmail());
        $this->assertFalse($cart->getShopper()->isBusiness());
        $this->assertFalse($cart->getShopper()->isGuest());
        $this->assertTrue($cart->getShopper()->isCustomer());
    }

    public function test_it_can_see_business_shopper()
    {
        $this->givenACustomerExists('foo@example.com', true);
        $this->whenIChooseCustomer('foo@example.com');

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));

        $this->assertTrue($cart->getShopper()->isBusiness());
    }

    public function test_it_uses_customer_billing_address()
    {
        $customer = $this->givenACustomerExists('foo@example.com');
        $address = (new Address(CountryId::fromString('BE'), 'street 123', 'bus 456', '2200', 'Herentals',));

        $this->customerApplication->updateBillingAddress(new UpdateBillingAddress(
            $customer->customerId->get(),
            ...array_values($address->toArray())
        ));

        $this->whenIChooseCustomer('foo@example.com');

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));

        $this->assertNull($cart->getShippingAddress());
        $this->assertEquals(array_values($address->toArray()), [
            $cart->getBillingAddress()->getCountryId(),
            $cart->getBillingAddress()->getLine1(),
            $cart->getBillingAddress()->getLine2(),
            $cart->getBillingAddress()->getPostalCode(),
            $cart->getBillingAddress()->getCity(),
        ]);
    }

    public function test_it_uses_customer_shipping_address()
    {
        $customer = $this->givenACustomerExists('foo@example.com');
        $address = (new Address(CountryId::fromString('BE'), 'street 123', 'bus 456', '2200', 'Herentals',));

        $this->customerApplication->updateShippingAddress(new UpdateShippingAddress(
            $customer->customerId->get(),
            ...array_values($address->toArray())
        ));

        $this->whenIChooseCustomer('foo@example.com');

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));

        $this->assertNull($cart->getBillingAddress());
        $this->assertEquals(array_values($address->toArray()), [
            $cart->getShippingAddress()->getCountryId(),
            $cart->getShippingAddress()->getLine1(),
            $cart->getShippingAddress()->getLine2(),
            $cart->getShippingAddress()->getPostalCode(),
            $cart->getShippingAddress()->getCity(),
        ]);
    }

    public function test_it_should_not_use_customer_billing_address_when_cart_address_is_already_filled_in()
    {
        $customer = $this->givenACustomerExists('foo@example.com');
        $address = (new Address(CountryId::fromString('BE'), 'street 123', 'bus 456', '2200', 'Herentals',));

        $this->customerApplication->updateBillingAddress(new UpdateBillingAddress(
            $customer->customerId->get(),
            ...array_values($address->toArray())
        ));

        $cartAddress = (new Address(CountryId::fromString('NL'), 'example 345', 'bus 7', '2230', 'Bouwel',));
        $this->cartApplication->updateBillingAddress(new \Thinktomorrow\Trader\Application\Cart\UpdateBillingAddress(
            $this->getOrder()->orderId->get(),
            ...array_values($cartAddress->toArray())
        ));

        $this->whenIChooseCustomer('foo@example.com');

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));

        $this->assertNull($cart->getShippingAddress());
        $this->assertEquals(array_values($cartAddress->toArray()), [
            $cart->getBillingAddress()->getCountryId(),
            $cart->getBillingAddress()->getLine1(),
            $cart->getBillingAddress()->getLine2(),
            $cart->getBillingAddress()->getPostalCode(),
            $cart->getBillingAddress()->getCity(),
        ]);
    }

    public function test_it_should_not_use_customer_shipping_address_when_cart_address_is_already_filled_in()
    {
        $customer = $this->givenACustomerExists('foo@example.com');
        $address = (new Address(CountryId::fromString('BE'), 'street 123', 'bus 456', '2200', 'Herentals',));

        $this->customerApplication->updateShippingAddress(new UpdateShippingAddress(
            $customer->customerId->get(),
            ...array_values($address->toArray())
        ));

        $cartAddress = (new Address(CountryId::fromString('NL'), 'example 345', 'bus 7', '2230', 'Bouwel',));
        $this->cartApplication->updateShippingAddress(new \Thinktomorrow\Trader\Application\Cart\UpdateShippingAddress(
            $this->getOrder()->orderId->get(),
            ...array_values($cartAddress->toArray())
        ));

        $this->whenIChooseCustomer('foo@example.com');

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));

        $this->assertNull($cart->getBillingAddress());
        $this->assertEquals(array_values($cartAddress->toArray()), [
            $cart->getShippingAddress()->getCountryId(),
            $cart->getShippingAddress()->getLine1(),
            $cart->getShippingAddress()->getLine2(),
            $cart->getShippingAddress()->getPostalCode(),
            $cart->getShippingAddress()->getCity(),
        ]);
    }
}
