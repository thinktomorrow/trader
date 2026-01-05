<?php
declare(strict_types=1);

namespace Tests\Acceptance\ShippingProfile;

use Tests\Acceptance\TestCase;
use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\ShippingProfile\CreateShippingProfile;
use Thinktomorrow\Trader\Application\ShippingProfile\CreateTariff;
use Thinktomorrow\Trader\Application\ShippingProfile\DeleteShippingProfile;
use Thinktomorrow\Trader\Application\ShippingProfile\DeleteTariff;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Events\ShippingProfileDeleted;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Events\TariffDeleted;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\CouldNotFindShippingProfile;

class DeleteShippingProfileTest extends TestCase
{
    use TestHelpers;

    public function test_it_can_delete_a_profile()
    {
        $shippingProfileId = $this->orderContext->apps()->shippingProfileApplication()->createShippingProfile(new CreateShippingProfile(
            'postnl',
            true,
            ['BE', 'NL'],
            ['foo' => 'bar']
        ));

        $this->orderContext->apps()->shippingProfileApplication()->deleteShippingProfile(new DeleteShippingProfile($shippingProfileId->get()));

        $this->assertEquals([
            new ShippingProfileDeleted($shippingProfileId),
        ], $this->orderContext->apps()->getEventDispatcher()->releaseDispatchedEvents());

        $this->expectException(CouldNotFindShippingProfile::class);
        $this->orderContext->repos()->shippingProfileRepository()->find($shippingProfileId);
    }

    public function test_it_can_delete_a_tariff()
    {
        $shippingProfileId = $this->orderContext->apps()->shippingProfileApplication()->createShippingProfile(new CreateShippingProfile(
            'postnl',
            true,
            ['BE', 'NL'],
            ['foo' => 'bar']
        ));

        $tariffId = $this->orderContext->apps()->shippingProfileApplication()->createTariff(new CreateTariff($shippingProfileId->get(), '50', '10', '30'));

        $this->orderContext->apps()->shippingProfileApplication()->deleteTariff(new DeleteTariff($shippingProfileId->get(), $tariffId->get()));

        $this->assertEquals([
            new TariffDeleted($shippingProfileId, $tariffId),
        ], $this->orderContext->apps()->getEventDispatcher()->releaseDispatchedEvents());

        $shippingProfile = $this->orderContext->repos()->shippingProfileRepository()->find($shippingProfileId);
        $this->assertCount(0, $shippingProfile->getTariffs());
    }
}
