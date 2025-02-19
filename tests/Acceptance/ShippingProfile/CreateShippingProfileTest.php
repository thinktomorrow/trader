<?php
declare(strict_types=1);

namespace Tests\Acceptance\ShippingProfile;

use Money\Money;
use Thinktomorrow\Trader\Application\ShippingProfile\CreateShippingProfile;
use Thinktomorrow\Trader\Application\ShippingProfile\CreateTariff;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProviderId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Tariff;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\TariffId;

class CreateShippingProfileTest extends ShippingProfileContext
{
    public function test_it_can_create_a_shipping_profile()
    {
        $shippingProfileId = $this->shippingProfileApplication->createShippingProfile(new CreateShippingProfile(
            'postnl',
            false,
            ['BE', 'NL'],
            ['foo' => 'bar']
        ));

        $shippingProfile = $this->shippingProfileRepository->find($shippingProfileId);

        $this->assertInstanceOf(ShippingProfileId::class, $shippingProfileId);
        $this->assertEquals($shippingProfileId, $shippingProfile->shippingProfileId);
        $this->assertEquals(ShippingProviderId::fromString('postnl'), $shippingProfile->getProvider());
        $this->assertFalse($shippingProfile->requiresAddress());
        $this->assertEquals([
            CountryId::fromString('BE'),
            CountryId::fromString('NL'),
        ], $shippingProfile->getCountryIds());
        $this->assertEquals(['foo' => 'bar'], $shippingProfile->getData());
    }

    public function test_it_can_create_a_tariff()
    {
        $shippingProfileId = $this->shippingProfileApplication->createShippingProfile(new CreateShippingProfile(
            'postnl',
            true,
            ['BE', 'NL'],
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
