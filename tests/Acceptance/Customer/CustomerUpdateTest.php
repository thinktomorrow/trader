<?php
declare(strict_types=1);

namespace Tests\Acceptance\Customer;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Common\Address\Address;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Application\Customer\UpdateData;
use Thinktomorrow\Trader\Application\Customer\UpdateLocale;
use Thinktomorrow\Trader\Application\Customer\UpdateBillingAddress;
use Thinktomorrow\Trader\Application\Customer\UpdateShippingAddress;

class CustomerUpdateTest extends CustomerContext
{
    public function test_it_can_update_general_data()
    {
        $customer = $this->givenACustomerExists('ben@tt.be');

        $data = [
            'firstname' => 'Ben',
            'lastname' => 'Cavens',
            'salutation' => 'mr',
        ];

        $this->customerApplication->updateData(new UpdateData(
            $customer->customerId->get(),
            $data
        ));

        $customer = $this->customerRepository->find($customer->customerId);

        $this->assertEquals($data, $customer->getData());
    }

    public function test_when_updating_general_data_it_keeps_existing_data_intact_but_overwrites_if_needed()
    {
        $customer = $this->givenACustomerExists('ben@tt.be');

        $this->customerApplication->updateData(new UpdateData(
            $customer->customerId->get(), ['firstname' => 'Ben', 'lastname' => 'Cavens']
        ));

        $this->customerApplication->updateData(new UpdateData(
            $customer->customerId->get(), ['firstname' => 'Benjamin']
        ));

        $customer = $this->customerRepository->find($customer->customerId);

        $this->assertEquals([
            'firstname' => 'Benjamin',
            'lastname' => 'Cavens',
        ], $customer->getData());
    }

    public function test_it_can_update_preferred_locale()
    {
        $customer = $this->givenACustomerExists('ben@tt.be');

        $this->customerApplication->updateLocale(new UpdateLocale(
            $customer->customerId->get(), 'fr-be'
        ));

        $customer = $this->customerRepository->find($customer->customerId);

        $this->assertEquals(Locale::fromString('fr-be'), $customer->getLocale());
    }

    public function test_it_can_update_billing_address()
    {
        $customer = $this->givenACustomerExists('ben@tt.be');

        $this->assertNull($customer->getBillingAddress());

        $address = (new Address(CountryId::fromString('BE'), 'street 123','bus 456','2200','Herentals',));
        $this->customerApplication->updateBillingAddress(new UpdateBillingAddress(
            $customer->customerId->get(),
            ...array_values($address->toArray())
        ));

        $customer = $this->customerRepository->find($customer->customerId);

        $this->assertEquals($address, $customer->getBillingAddress()->getAddress());
    }

    public function test_it_can_update_shipping_address()
    {
        $customer = $this->givenACustomerExists('ben@tt.be');

        $this->assertNull($customer->getShippingAddress());

        $address = (new Address(CountryId::fromString('BE'), 'street 123','bus 456','2200','Herentals',));
        $this->customerApplication->updateShippingAddress(new UpdateShippingAddress(
            $customer->customerId->get(),
            ...array_values($address->toArray())
        ));

        $customer = $this->customerRepository->find($customer->customerId);

        $this->assertEquals($address, $customer->getShippingAddress()->getAddress());
    }
}
