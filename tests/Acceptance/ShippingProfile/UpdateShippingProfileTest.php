<?php
declare(strict_types=1);

namespace Tests\Acceptance\ShippingProfile;

use Money\Money;
use Thinktomorrow\Trader\Application\ShippingProfile\CreateShippingProfile;
use Thinktomorrow\Trader\Application\ShippingProfile\CreateTariff;
use Thinktomorrow\Trader\Application\ShippingProfile\UpdateShippingProfile;
use Thinktomorrow\Trader\Application\ShippingProfile\UpdateTariff;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

class UpdateShippingProfileTest extends ShippingProfileContext
{
    /** @test */
    public function it_can_update_a_profile()
    {
        $shippingProfileId = $this->shippingProfileApplication->createShippingProfile(new CreateShippingProfile(
            true,
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $this->shippingProfileApplication->updateShippingProfile(new UpdateShippingProfile(
            $shippingProfileId->get(),
            ['BE'],
            ['foo' => 'baz']
        ));

        $this->assertEquals([
            CountryId::fromString('BE'),
        ], $this->shippingProfileRepository->find($shippingProfileId)->getCountryIds());
        $this->assertEquals(['foo' => 'baz'], $this->shippingProfileRepository->find($shippingProfileId)->getData());
    }

    /** @test */
    public function it_can_update_a_tariff()
    {
        $shippingProfileId = $this->shippingProfileApplication->createShippingProfile(new CreateShippingProfile(
            true,
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $tariffId = $this->shippingProfileApplication->createTariff(new CreateTariff($shippingProfileId->get(), '50', '10', '30'));

        $this->shippingProfileApplication->updateTariff(new UpdateTariff($tariffId->get(), $shippingProfileId->get(), '60', '20', null));

        $tariff = $this->shippingProfileRepository->find($shippingProfileId)->findTariff($tariffId);

        $this->assertEquals(Money::EUR('60'), $tariff->getRate());
        $this->assertEquals('20', $tariff->getMappedData()['from']);
        $this->assertNull($tariff->getMappedData()['to']);
    }
}
