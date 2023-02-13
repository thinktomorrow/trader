<?php

namespace Tests\Acceptance\Cart;

use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\CouldNotFindShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileState;

class ChooseShippingProfileTest extends CartContext
{
    public function test_it_can_choose_profile()
    {
        $this->givenShippingCostsForAPurchaseOfEur(2, 0, 10, [], 'bpost_home', false);
        $this->whenIChooseShipping('bpost_home');

        // Assert all is present
        $cart = $this->cartRepository->findCart($this->getOrder()->orderId);
        $this->assertNotNull($cart->getShipping());
    }

    public function test_it_cannot_choose_profile_when_none_is_online()
    {
        $profile = ShippingProfile::create(ShippingProfileId::fromString('foobar'), true);
        $profile->updateState(ShippingProfileState::offline);
        $this->shippingProfileRepository->save($profile);

        $this->whenIChooseShipping('foobar');

        $cart = $this->cartRepository->findCart($this->getOrder()->orderId);
        $this->assertNull($cart->getShipping());
    }

    public function test_it_cannot_choose_profile_when_method_has_country_restriction_and_shipping_country_is_not_given()
    {
        $profile = ShippingProfile::create(ShippingProfileId::fromString('foobar'), true);
        $profile->addCountry(CountryId::fromString('LU'));
        $this->shippingProfileRepository->save($profile);

        $this->whenIChooseShipping('foobar');

        $cart = $this->cartRepository->findCart($this->getOrder()->orderId);
        $this->assertNull($cart->getShipping());
    }

    public function test_it_cannot_choose_profile_when_method_has_country_but_does_not_require_address()
    {
        $profile = ShippingProfile::create(ShippingProfileId::fromString('foobar'), false);
        $profile->addCountry(CountryId::fromString('LU'));
        $this->shippingProfileRepository->save($profile);

        $this->whenIChooseShipping('foobar');

        $cart = $this->cartRepository->findCart($this->getOrder()->orderId);
        $this->assertNull($cart->getShipping());
    }

    public function test_it_can_choose_profile_when_it_is_allowed_for_given_shipping_country()
    {
        $profile = ShippingProfile::create(ShippingProfileId::fromString('foobar'), true);
        $profile->addCountry(CountryId::fromString('LU'));
        $this->shippingProfileRepository->save($profile);

        $this->givenOrderHasAShippingCountry('LU');
        $this->whenIChooseShipping('foobar');

        $cart = $this->cartRepository->findCart($this->getOrder()->orderId);
        $this->assertNotNull($cart->getShipping());
    }

    public function test_it_cannot_choose_profile_when_none_is_allowed_for_given_shipping_country()
    {
        $profile = ShippingProfile::create(ShippingProfileId::fromString('foobar'), true);
        $profile->addCountry(CountryId::fromString('LU'));
        $this->shippingProfileRepository->save($profile);

        $this->givenOrderHasAShippingCountry('BE');
        $this->whenIChooseShipping('foobar');

        $cart = $this->cartRepository->findCart($this->getOrder()->orderId);
        $this->assertNull($cart->getShipping());
    }

    public function test_it_halts_when_profile_id_does_not_exist()
    {
        $this->expectException(CouldNotFindShippingProfile::class);

        $this->whenIChooseShipping('xxx');
    }
}
