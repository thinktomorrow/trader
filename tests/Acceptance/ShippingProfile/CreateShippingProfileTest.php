<?php
declare(strict_types=1);

namespace Tests\Acceptance\ShippingProfile;

use Money\Money;
use Thinktomorrow\Trader\Application\ShippingProfile\CreateShippingProfile;
use Thinktomorrow\Trader\Application\ShippingProfile\CreateTariff;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Tariff;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\TariffId;

class CreateShippingProfileTest extends ShippingProfileContext
{
    /** @test */
    public function it_can_create_a_shipping_profile()
    {
        $shippingProfileId = $this->shippingProfileApplication->createShippingProfile(new CreateShippingProfile(
            false,
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $this->assertInstanceOf(ShippingProfileId::class, $shippingProfileId);
        $this->assertEquals($shippingProfileId, $this->shippingProfileRepository->find($shippingProfileId)->shippingProfileId);
        $this->assertFalse($this->shippingProfileRepository->find($shippingProfileId)->requiresAddress());
        $this->assertEquals([
            CountryId::fromString('BE'),
            CountryId::fromString('NL'),
        ], $this->shippingProfileRepository->find($shippingProfileId)->getCountryIds());
        $this->assertEquals(['foo' => 'bar'], $this->shippingProfileRepository->find($shippingProfileId)->getData());
    }

    /** @test */
    public function it_can_create_a_tariff()
    {
        $shippingProfileId = $this->shippingProfileApplication->createShippingProfile(new CreateShippingProfile(
            true,
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $tariffId = $this->shippingProfileApplication->createTariff(new CreateTariff($shippingProfileId->get(), '50', '10', '30'));

        $this->assertInstanceOf(TariffId::class, $tariffId);
        $this->assertInstanceOf(Tariff::class, $tariff = $this->shippingProfileRepository->find($shippingProfileId)->findTariff($tariffId));
        $this->assertEquals(Money::EUR('50'), $tariff->getRate());
        $this->assertEquals('10', $tariff->getMappedData()['from']);
        $this->assertEquals('30', $tariff->getMappedData()['to']);
    }
}
