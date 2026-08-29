<?php

namespace Tests\Acceptance\Cart;

use Thinktomorrow\Trader\Application\Cart\ShippingProfile\Eligibility\ProfileMustBeOnline;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\Eligibility\ProfileMustSupportShippingCountry;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\Eligibility\ShippingProfileEligibility;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\Eligibility\ShippingProfileEligibilityRule;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\CouldNotFindShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\ShippingProfileIsNotAvailable;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileState;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProviderId;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

class ChooseShippingProfileTest extends CartContext
{
    public function test_it_can_choose_profile()
    {
        $this->givenShippingCostsForAPurchaseOfEur(2, 0, 10, [], 'bpost_home', false);
        $this->whenIChooseShipping('bpost_home');

        // Assert all is present
        $cart = $this->orderContext->repos()->cartRepository()->findCart($this->getOrder()->orderId);
        $this->assertNotNull($cart->getShipping());
    }

    public function test_it_cannot_choose_profile_when_none_is_online()
    {
        $profile = ShippingProfile::create(ShippingProfileId::fromString('foobar'), ShippingProviderId::fromString('postnl'), true);
        $profile->updateState(ShippingProfileState::offline);
        $this->orderContext->repos()->shippingProfileRepository()->save($profile);

        $this->expectException(ShippingProfileIsNotAvailable::class);

        $this->whenIChooseShipping('foobar');
    }

    public function test_it_cannot_choose_profile_when_method_has_country_restriction_and_shipping_country_is_not_given()
    {
        $profile = ShippingProfile::create(ShippingProfileId::fromString('foobar'), ShippingProviderId::fromString('postnl'), true);
        $profile->addCountry(CountryId::fromString('LU'));
        $this->orderContext->repos()->shippingProfileRepository()->save($profile);

        $this->expectException(ShippingProfileIsNotAvailable::class);

        $this->whenIChooseShipping('foobar');
    }

    public function test_it_cannot_choose_profile_when_method_has_country_but_does_not_require_address()
    {
        $profile = ShippingProfile::create(ShippingProfileId::fromString('foobar'), ShippingProviderId::fromString('postnl'), false);
        $profile->addCountry(CountryId::fromString('LU'));
        $this->orderContext->repos()->shippingProfileRepository()->save($profile);

        $this->expectException(ShippingProfileIsNotAvailable::class);

        $this->whenIChooseShipping('foobar');
    }

    public function test_it_can_choose_profile_when_it_is_allowed_for_given_shipping_country()
    {
        $profile = ShippingProfile::create(ShippingProfileId::fromString('foobar'), ShippingProviderId::fromString('postnl'), true);
        $profile->addCountry(CountryId::fromString('LU'));
        $this->orderContext->repos()->shippingProfileRepository()->save($profile);

        $this->givenOrderHasAShippingCountry('LU');
        $this->whenIChooseShipping('foobar');

        $cart = $this->orderContext->repos()->cartRepository()->findCart($this->getOrder()->orderId);
        $this->assertNotNull($cart->getShipping());
    }

    public function test_it_cannot_choose_profile_when_none_is_allowed_for_given_shipping_country()
    {
        $profile = ShippingProfile::create(ShippingProfileId::fromString('foobar'), ShippingProviderId::fromString('postnl'), true);
        $profile->addCountry(CountryId::fromString('LU'));
        $this->orderContext->repos()->shippingProfileRepository()->save($profile);

        $this->givenOrderHasAShippingCountry('BE');
        $this->expectException(ShippingProfileIsNotAvailable::class);

        $this->whenIChooseShipping('foobar');
    }

    public function test_it_halts_when_profile_id_does_not_exist()
    {
        $this->expectException(CouldNotFindShippingProfile::class);

        $this->whenIChooseShipping('xxx');
    }

    public function test_it_can_choose_an_unrestricted_profile_when_order_has_shipping_country(): void
    {
        $profile = ShippingProfile::create(ShippingProfileId::fromString('foobar'), ShippingProviderId::fromString('postnl'), true);
        $this->orderContext->repos()->shippingProfileRepository()->save($profile);
        $this->givenOrderHasAShippingCountry('BE');

        $this->whenIChooseShipping('foobar');

        $cart = $this->orderContext->repos()->cartRepository()->findCart($this->getOrder()->orderId);
        $this->assertNotNull($cart->getShipping());
    }

    public function test_failed_replacement_keeps_existing_shipping_selection(): void
    {
        $validProfile = ShippingProfile::create(ShippingProfileId::fromString('valid'), ShippingProviderId::fromString('postnl'), true);
        $invalidProfile = ShippingProfile::create(ShippingProfileId::fromString('invalid'), ShippingProviderId::fromString('postnl'), true);
        $invalidProfile->updateState(ShippingProfileState::offline);
        $this->orderContext->repos()->shippingProfileRepository()->save($validProfile);
        $this->orderContext->repos()->shippingProfileRepository()->save($invalidProfile);
        $this->whenIChooseShipping('valid');

        try {
            $this->whenIChooseShipping('invalid');
            $this->fail('Expected unavailable shipping profile exception.');
        } catch (ShippingProfileIsNotAvailable) {
            $cart = $this->orderContext->repos()->cartRepository()->findCart($this->getOrder()->orderId);
            $this->assertSame('valid', $cart->getShipping()->getShippingProfileId());
        }
    }

    public function test_project_eligibility_rule_can_reject_explicit_selection(): void
    {
        $profile = ShippingProfile::create(ShippingProfileId::fromString('foobar'), ShippingProviderId::fromString('postnl'), true);
        $this->orderContext->repos()->shippingProfileRepository()->save($profile);
        (new TestContainer)->add(ShippingProfileEligibility::class, new ShippingProfileEligibility(
            new class implements ShippingProfileEligibilityRule
            {
                public function isEligible(Order $order, ShippingProfile $shippingProfile): bool
                {
                    return false;
                }
            }
        ));

        try {
            $this->whenIChooseShipping('foobar');
            $this->fail('Expected unavailable shipping profile exception.');
        } catch (ShippingProfileIsNotAvailable) {
            $this->assertNull($this->orderContext->repos()->cartRepository()->findCart($this->getOrder()->orderId)->getShipping());
        } finally {
            (new TestContainer)->add(ShippingProfileEligibility::class, new ShippingProfileEligibility(
                new ProfileMustBeOnline,
                new ProfileMustSupportShippingCountry,
            ));
        }
    }
}
