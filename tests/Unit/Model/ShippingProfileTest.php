<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileState;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProviderId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Tariff;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\TariffId;

class ShippingProfileTest extends TestCase
{
    public function test_it_can_create_a_shipping_profile()
    {
        $shippingProfile = ShippingProfile::create(
            $shippingProfileId = ShippingProfileId::fromString('yyy'),
            $shippingProviderId = ShippingProviderId::fromString('postnl'),
            false,
        );

        $this->assertEquals([
            'shipping_profile_id' => $shippingProfileId->get(),
            'provider_id' => $shippingProviderId->get(),
            'state' => ShippingProfileState::online->value,
            'requires_address' => false,
            'data' => "[]",
        ], $shippingProfile->getMappedData());

        $this->assertEquals([
            Tariff::class => [],
            CountryId::class => [],
        ], $shippingProfile->getChildEntities());
    }

    public function test_it_can_be_build_from_raw_data()
    {
        $shippingProfile = $this->createdShippingProfile();

        $this->assertEquals(ShippingProfileId::fromString('yyy'), $shippingProfile->shippingProfileId);
        $this->assertEquals(ShippingProviderId::fromString('postnl'), $shippingProfile->getProvider());
        $this->assertEquals(ShippingProfileState::offline, $shippingProfile->getState());
        $this->assertTrue($shippingProfile->requiresAddress());
        $this->assertEquals('bar', $shippingProfile->getData('foo'));
        $this->assertCount(2, $shippingProfile->getChildEntities()[Tariff::class]);
        $this->assertEquals([
            'shipping_profile_id' => 'yyy',
            'tariff_id' => 'xxx',
            'rate' => '500',
            'from' => '0',
            'to' => '1000',
        ], $shippingProfile->getChildEntities()[Tariff::class][0]);

        $this->assertCount(2, $shippingProfile->getChildEntities()[CountryId::class]);
    }

    public function test_it_can_update_provider()
    {
        $shippingProfile = $this->createdShippingProfile();

        $shippingProfile->updateProvider($updatedProviderId = ShippingProviderId::fromString('bpack'));

        $this->assertEquals($updatedProviderId, $shippingProfile->getProvider());
    }

    public function test_it_can_add_a_tariff()
    {
        $shippingProfile = $this->createdShippingProfile();

        $shippingProfile->addTariff(
            Tariff::create(
                TariffId::fromString('xxx'),
                $shippingProfile->shippingProfileId,
                Money::EUR(30),
                Money::EUR(3001),
                Money::EUR(4000)
            )
        );

        $this->assertCount(3, $shippingProfile->getChildEntities()[Tariff::class]);
    }

    public function test_it_can_update_countries()
    {
        $shippingProfile = $this->createdShippingProfile();

        $countries = [
            CountryId::fromString('FR'),
            CountryId::fromString('NL'),
        ];

        $shippingProfile->updateCountries($countries);

        $this->assertCount(2, $shippingProfile->getCountryIds());
        $this->assertCount(2, $shippingProfile->getChildEntities()[CountryId::class]);
        $this->assertEquals($countries, $shippingProfile->getCountryIds());

        $this->assertTrue($shippingProfile->hasCountry(CountryId::fromString('FR')));
        $this->assertTrue($shippingProfile->hasCountry(CountryId::fromString('NL')));
        $this->assertFalse($shippingProfile->hasCountry(CountryId::fromString('BE')));
    }

    public function test_it_can_add_country()
    {
        $shippingProfile = $this->createdShippingProfile();

        $shippingProfile->addCountry(CountryId::fromString('FR'));

        $this->assertCount(3, $shippingProfile->getCountryIds());
        $this->assertEquals([
            CountryId::fromString('BE'),
            CountryId::fromString('NL'),
            CountryId::fromString('FR'),
        ], $shippingProfile->getCountryIds());
    }

    public function test_it_can_delete_country()
    {
        $shippingProfile = $this->createdShippingProfile();

        $shippingProfile->deleteCountry(CountryId::fromString('BE'));

        $this->assertCount(1, $shippingProfile->getCountryIds());
        $this->assertEquals([
            CountryId::fromString('NL'),
        ], $shippingProfile->getCountryIds());
    }

    public function test_it_can_check_if_tariff_is_within_range()
    {
        $tariff = Tariff::create(TariffId::fromString('xxx'), ShippingProfileId::fromString('yyy'), Cash::make(100), Cash::make(10), Cash::make(1000));
        $this->assertTrue($tariff->withinRange(Cash::make(10)));
        $this->assertTrue($tariff->withinRange(Cash::make(1000)));
        $this->assertFalse($tariff->withinRange(Cash::make(1001)));

        $tariff = Tariff::create(TariffId::fromString('xxx'), ShippingProfileId::fromString('yyy'), Cash::make(100), Cash::make(10), null);
        $this->assertTrue($tariff->withinRange(Cash::make(1001)));
        $this->assertTrue($tariff->withinRange(Cash::make(100001)));
    }

    private function createdShippingProfile(): ShippingProfile
    {
        return ShippingProfile::fromMappedData([
            'shipping_profile_id' => 'yyy',
            'provider_id' => 'postnl',
            'requires_address' => true,
            'state' => ShippingProfileState::offline->value,
            'data' => json_encode(['foo' => 'bar']),
        ], [
            Tariff::class => [
                [
                    'tariff_id' => 'xxx',
                    'rate' => '500',
                    'from' => '0',
                    'to' => '1000',
                ],
                [
                    'tariff_id' => 'yyy',
                    'rate' => '0',
                    'from' => '1001',
                    'to' => '2000',
                ],
            ],
            CountryId::class => [
                ['country_id' => 'BE'],
                ['country_id' => 'NL'],
            ],
        ]);
    }
}
