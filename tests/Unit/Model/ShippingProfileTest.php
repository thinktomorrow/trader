<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Tariff;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\TariffNumber;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCountry;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;

class ShippingProfileTest extends TestCase
{
    /** @test */
    public function it_can_create_a_shipping_profile()
    {
        $shippingProfile = ShippingProfile::create(
            $shippingProfileId = ShippingProfileId::fromString('yyy')
        );

        $this->assertEquals([
            'shipping_profile_id' => $shippingProfileId->get(),
        ], $shippingProfile->getMappedData());

        $this->assertEquals([
            Tariff::class => [],
            ShippingCountry::class => [],
        ], $shippingProfile->getChildEntities());
    }

    /** @test */
    public function it_can_be_build_from_raw_data()
    {
        $shippingProfile = $this->createdShippingProfile();

        $this->assertEquals(ShippingProfileId::fromString('yyy'), $shippingProfile->shippingProfileId);
        $this->assertCount(2, $shippingProfile->getChildEntities()[Tariff::class]);
        $this->assertEquals([
            'shipping_profile_id'    => 'yyy',
            'tariff_number'    => 1,
            'rate'             => '500',
            'from'             => '0',
            'to'               => '1000',
        ], $shippingProfile->getChildEntities()[Tariff::class][0]->getMappedData());

        $this->assertCount(2, $shippingProfile->getChildEntities()[ShippingCountry::class]);
    }

    /** @test */
    public function it_can_add_a_tariff()
    {
        $shippingProfile = $this->createdShippingProfile();

        $shippingProfile->addTariff(
            TariffNumber::fromInt(123),
            Money::EUR(30),
            Money::EUR(3001),
            Money::EUR(4000)
        );

        $this->assertCount(3, $shippingProfile->getChildEntities()[Tariff::class]);
    }

    /** @test */
    public function it_can_update_a_tariff()
    {
        $shippingProfile = $this->createdShippingProfile();

        $shippingProfile->updateTariff(
            TariffNumber::fromInt(2),
            Money::EUR(30),
            Money::EUR(3001),
            Money::EUR(4000)
        );

        $this->assertCount(2, $shippingProfile->getChildEntities()[Tariff::class]);

        $this->assertEquals([
            'shipping_profile_id' => 'yyy',
            'tariff_number'    => 2,
            'rate'             => '30',
            'from'             => '3001',
            'to'               => '4000',
        ], $shippingProfile->getChildEntities()[Tariff::class][1]->getMappedData());
    }

    /** @test */
    public function it_can_delete_a_tariff()
    {
        $shippingProfile = $this->createdShippingProfile();

        $shippingProfile->deleteTariff(
            TariffNumber::fromInt(1),
        );

        $this->assertCount(1, $shippingProfile->getChildEntities()[Tariff::class]);
    }

    private function createdShippingProfile(): ShippingProfile
    {
        return ShippingProfile::fromMappedData([
            'shipping_profile_id' => 'yyy',
        ], [
            Tariff::class => [
                [
                    'tariff_number'    => 1,
                    'rate'             => '500',
                    'from'             => '0',
                    'to'               => '1000',
                ],
                [
                    'tariff_number'    => 2,
                    'rate'             => '0',
                    'from'             => '1001',
                    'to'               => '2000',
                ]
            ],
            ShippingCountry::class => ['BE', 'NL'],
        ]);
    }
}
