<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Testing\Order\OrderContext;

class ShippingProfileForCartRepositoryTest extends TestCase
{
    public function test_it_can_find_shipping_profiles_for_cart()
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $orderContext->createShippingProfile();

            $repository = $orderContext->repos()->shippingProfileRepository();
            $this->assertCount(1, $repository->findAllShippingProfilesForCart());
        }
    }

    public function test_it_can_find_profiles_for_cart_with_matching_countries()
    {
        foreach (OrderContext::drivers() as $orderContext) {

            // Create payment method with country BE
            $shippingProfile = $orderContext->dontPersist()->createShippingProfile();
            $country = $orderContext->persist()->createCountry('BE');

            $repository = $orderContext->repos()->shippingProfileRepository();

            $shippingProfile->addCountry($country->countryId);
            $repository->save($shippingProfile);

            $this->assertCount(1, $repository->findAllShippingProfilesForCart('BE'));
            $this->assertCount(0, $repository->findAllShippingProfilesForCart('NL'));
        }
    }
}
