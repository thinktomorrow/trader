<?php
declare(strict_types=1);

namespace Tests\Acceptance\Customer;

use Thinktomorrow\Trader\Application\Customer\Read\CustomerRead;

final class CustomerReadTest extends CustomerContext
{
    public function test_it_can_find_customer_read()
    {
        $customer = $this->givenACustomerExists('ben@tt.be');

        $customerRead = $this->customerReadRepository->findCustomer($customer->customerId);

        $this->assertInstanceOf(CustomerRead::class, $customerRead);
        $this->assertEquals($customer->customerId->get(), $customerRead->getCustomerId());
        $this->assertEquals($customer->getEmail()->get(), $customerRead->getEmail());
        $this->assertEquals($customer->getLocale(), $customerRead->getCustomerLocale());
        $this->assertEquals($customer->isBusiness(), $customerRead->isBusiness());
        $this->assertEquals($customer->getShippingAddress(), $customerRead->getShippingAddress());
        $this->assertEquals($customer->getBillingAddress(), $customerRead->getBillingAddress());
    }

    public function test_it_can_set_address()
    {
        $customer = $this->givenACustomerExists('ben@tt.be');
        $this->andCustomerHasBillingAddress($customer->customerId->get(), 'BE', 'street 123', null, '2200', 'Herentals');
        $this->andCustomerHasShippingAddress($customer->customerId->get(), 'NL', 'street 456', null, '2200 AB', 'Eindhoven');

        $customer = $this->customerRepository->find($customer->customerId);
        $customerRead = $this->customerReadRepository->findCustomer($customer->customerId);

        $this->assertEquals($customer->getBillingAddress()->getAddress()->toArray(), [
            'country_id' => $customerRead->getBillingAddress()->getCountryId(),
            'line1' => $customerRead->getBillingAddress()->getLine1(),
            'line2' => $customerRead->getBillingAddress()->getLine2(),
            'postal_code' => $customerRead->getBillingAddress()->getPostalCode(),
            'city' => $customerRead->getBillingAddress()->getCity(),
        ]);

        $this->assertEquals($customer->getShippingAddress()->getAddress()->toArray(), [
            'country_id' => $customerRead->getShippingAddress()->getCountryId(),
            'line1' => $customerRead->getShippingAddress()->getLine1(),
            'line2' => $customerRead->getShippingAddress()->getLine2(),
            'postal_code' => $customerRead->getShippingAddress()->getPostalCode(),
            'city' => $customerRead->getShippingAddress()->getCity(),
        ]);
    }
}
