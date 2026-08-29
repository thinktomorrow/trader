<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Support\Facades\DB;
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

            // Create profile with country BE
            $shippingProfile = $orderContext->dontPersist()->createShippingProfile();
            $country = $orderContext->persist()->createCountry('BE');

            $repository = $orderContext->repos()->shippingProfileRepository();

            $shippingProfile->addCountry($country->countryId);
            $repository->save($shippingProfile);

            $this->assertCount(1, $repository->findAllShippingProfilesForCart('BE'));
            $this->assertCount(0, $repository->findAllShippingProfilesForCart('NL'));
        }
    }

    public function test_unrestricted_profiles_are_available_with_or_without_country(): void
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $orderContext->createShippingProfile();

            $repository = $orderContext->repos()->shippingProfileRepository();

            $this->assertCount(1, $repository->findAllShippingProfilesForCart());
            $this->assertCount(1, $repository->findAllShippingProfilesForCart('BE'));
        }
    }

    public function test_restricted_profiles_are_not_available_without_country(): void
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $shippingProfile = $orderContext->dontPersist()->createShippingProfile();
            $country = $orderContext->persist()->createCountry('BE');
            $shippingProfile->addCountry($country->countryId);
            $orderContext->repos()->shippingProfileRepository()->save($shippingProfile);

            $this->assertCount(0, $orderContext->repos()->shippingProfileRepository()->findAllShippingProfilesForCart());
        }
    }

    public function test_inactive_country_restrictions_are_ignored(): void
    {
        $orderContext = OrderContext::mysql();
        $shippingProfile = $orderContext->dontPersist()->createShippingProfile();
        $country = $orderContext->persist()->createCountry('BE');
        $shippingProfile->addCountry($country->countryId);
        $orderContext->repos()->shippingProfileRepository()->save($shippingProfile);
        DB::table('trader_countries')->where('country_id', 'BE')->update(['active' => 0]);

        $repository = $orderContext->repos()->shippingProfileRepository();

        $this->assertCount(1, $repository->findAllShippingProfilesForCart());
        $this->assertCount(1, $repository->findAllShippingProfilesForCart('NL'));
    }
}
