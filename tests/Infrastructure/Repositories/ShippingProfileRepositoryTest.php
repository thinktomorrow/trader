<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Country\Country;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\CouldNotFindShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Testing\Order\OrderContext;

class ShippingProfileRepositoryTest extends TestCase
{
    public function test_it_can_save_and_find_a_profile()
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $shippingProfile = $orderContext->dontPersist()->createShippingProfile();

            $repository = $orderContext->repos()->shippingProfileRepository();

            $repository->save($shippingProfile);

            $this->assertEquals($shippingProfile, $repository->find($shippingProfile->shippingProfileId));
        }
    }

    public function test_it_can_delete_a_profile()
    {
        $profilesNotFound = 0;

        foreach (OrderContext::drivers() as $orderContext) {
            $shippingProfile = $orderContext->createShippingProfile();

            $repository = $orderContext->repos()->shippingProfileRepository();

            $repository->delete($shippingProfile->shippingProfileId);

            try {
                $repository->find($shippingProfile->shippingProfileId);
            } catch (CouldNotFindShippingProfile $e) {
                $profilesNotFound++;
            }
        }

        $this->assertCount($profilesNotFound, OrderContext::drivers());
    }

    public function test_it_can_generate_a_next_reference()
    {
        foreach (OrderContext::drivers() as $orderContext) {

            $repository = $orderContext->repos()->shippingProfileRepository();

            $this->assertInstanceOf(ShippingProfileId::class, $repository->nextReference());
        }
    }

    public function test_it_can_get_available_shipping_countries()
    {
        foreach (OrderContext::drivers() as $orderContext) {

            $repository = $orderContext->repos()->shippingProfileRepository();

            // Create profile with country BE
            $shippingProfile = $orderContext->dontPersist()->createShippingProfile();
            $shippingProfile->addCountry($orderContext->persist()->createCountry('BE')->countryId);
            $repository->save($shippingProfile);

            // Create profile with country NL
            $shippingProfile = $orderContext->dontPersist()->createShippingProfile('shipping-profile-bbb');
            $shippingProfile->addCountry($orderContext->persist()->createCountry('NL')->countryId);
            $repository->save($shippingProfile);

            $this->assertCount(2, $repository->getAvailableShippingCountries());
            $this->assertEquals([
                Country::fromMappedData($orderContext->dontPersist()->createCountry('BE')->getMappedData()),
                Country::fromMappedData($orderContext->dontPersist()->createCountry('NL')->getMappedData()),
            ], $repository->getAvailableShippingCountries());
        }
    }
}
