<?php

declare(strict_types=1);

namespace Tests\Acceptance\ShippingProfile;

use Money\Money;
use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\ShippingProfile\CreateShippingProfile;
use Thinktomorrow\Trader\Application\ShippingProfile\CreateTariff;
use Thinktomorrow\Trader\Application\ShippingProfile\UpdateShippingProfile;
use Thinktomorrow\Trader\Application\ShippingProfile\UpdateTariff;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProviderId;

class UpdateShippingProfileTest extends TestCase
{
    public function test_it_can_update_a_profile()
    {
        $shippingProfileId = $this->orderContext->apps()->shippingProfileApplication()->createShippingProfile(new CreateShippingProfile(
            'postnl',
            true,
            ['BE', 'NL'],
            ['foo' => 'bar']
        ));

        $this->orderContext->apps()->shippingProfileApplication()->updateShippingProfile(new UpdateShippingProfile(
            $shippingProfileId->get(),
            'bpack',
            false,
            ['BE'],
            ['foo' => 'baz']
        ));

        $shippingProfile = $this->orderContext->repos()->shippingProfileRepository()->find($shippingProfileId);

        $this->assertEquals([
            CountryId::fromString('BE'),
        ], $shippingProfile->getCountryIds());
        $this->assertEquals(ShippingProviderId::fromString('bpack'), $shippingProfile->getProvider());
        $this->assertFalse($shippingProfile->requiresAddress());
        $this->assertEquals(['foo' => 'baz'], $shippingProfile->getData());
    }

    public function test_it_can_update_a_tariff()
    {
        $shippingProfileId = $this->orderContext->apps()->shippingProfileApplication()->createShippingProfile(new CreateShippingProfile(
            'postnl',
            true,
            ['BE', 'NL'],
            ['foo' => 'bar']
        ));

        $tariffId = $this->orderContext->apps()->shippingProfileApplication()->createTariff(new CreateTariff($shippingProfileId->get(), '50', '10', '30'));

        $this->orderContext->apps()->shippingProfileApplication()->updateTariff(new UpdateTariff($tariffId->get(), $shippingProfileId->get(), '60', '20', null));

        $tariff = $this->orderContext->repos()->shippingProfileRepository()->find($shippingProfileId)->findTariff($tariffId);

        $this->assertEquals(Money::EUR('60'), $tariff->getRate());
        $this->assertEquals('20', $tariff->getMappedData()['from']);
        $this->assertNull($tariff->getMappedData()['to']);
    }
}
