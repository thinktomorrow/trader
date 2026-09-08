<?php

declare(strict_types=1);

namespace Tests\Acceptance\ShippingProfile;

use Money\Money;
use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\ShippingProfile\CreateShippingProfile;
use Thinktomorrow\Trader\Application\ShippingProfile\CreateTariff;
use Thinktomorrow\Trader\Application\ShippingProfile\ShippingProfileApplication;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProviderId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Tariff;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\TariffId;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

class CreateShippingProfileTest extends TestCase
{
    public function test_it_can_create_a_shipping_profile()
    {
        $shippingProfileId = $this->orderContext->apps()->shippingProfileApplication()->createShippingProfile(new CreateShippingProfile(
            'postnl',
            false,
            ['BE', 'NL'],
            ['foo' => 'bar']
        ));

        $shippingProfile = $this->orderContext->repos()->shippingProfileRepository()->find($shippingProfileId);

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
        $shippingProfileId = $this->orderContext->apps()->shippingProfileApplication()->createShippingProfile(new CreateShippingProfile(
            'postnl',
            true,
            ['BE', 'NL'],
            ['foo' => 'bar']
        ));

        $tariffId = $this->orderContext->apps()->shippingProfileApplication()->createTariff(new CreateTariff($shippingProfileId->get(), '50', '10', '30'));

        $this->assertInstanceOf(TariffId::class, $tariffId);
        $this->assertInstanceOf(Tariff::class, $tariff = $this->orderContext->repos()->shippingProfileRepository()->find($shippingProfileId)->findTariff($tariffId));
        $this->assertEquals(Money::EUR('50'), $tariff->getRate());
        $this->assertEquals('10', $tariff->getMappedData()['from']);
        $this->assertEquals('30', $tariff->getMappedData()['to']);
    }

    public function test_tariff_uses_configured_default_tax_mode_when_mode_is_omitted(): void
    {
        $application = new ShippingProfileApplication(
            $this->orderContext->apps()->getEventDispatcher(),
            $this->orderContext->repos()->shippingProfileRepository(),
            new TestTraderConfig(['does_tariff_input_includes_vat' => true]),
        );
        $shippingProfileId = $application->createShippingProfile(new CreateShippingProfile('postnl', false, [], []));

        $tariffId = $application->createTariff(new CreateTariff($shippingProfileId->get(), '50', '10', '30'));
        $tariff = $this->orderContext->repos()->shippingProfileRepository()->find($shippingProfileId)->findTariff($tariffId);
        $this->assertSame(TaxMode::Inclusive, $tariff->getTaxMode());

        $exclusiveProfileId = $application->createShippingProfile(new CreateShippingProfile('bpost', false, [], []));
        $exclusiveTariffId = $application->createTariff(new CreateTariff($exclusiveProfileId->get(), '60', '30', null, TaxMode::Exclusive->value));
        $exclusiveTariff = $this->orderContext->repos()->shippingProfileRepository()->find($exclusiveProfileId)->findTariff($exclusiveTariffId);
        $this->assertSame(TaxMode::Exclusive, $exclusiveTariff->getTaxMode());
    }
}
