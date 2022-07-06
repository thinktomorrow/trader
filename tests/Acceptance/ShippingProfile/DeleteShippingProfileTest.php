<?php
declare(strict_types=1);

namespace Tests\Acceptance\ShippingProfile;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\ShippingProfile\CreateShippingProfile;
use Thinktomorrow\Trader\Application\ShippingProfile\CreateTariff;
use Thinktomorrow\Trader\Application\ShippingProfile\DeleteShippingProfile;
use Thinktomorrow\Trader\Application\ShippingProfile\DeleteTariff;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Events\ShippingProfileDeleted;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Events\TariffDeleted;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\CouldNotFindShippingProfile;

class DeleteShippingProfileTest extends ShippingProfileContext
{
    use TestHelpers;

    /** @test */
    public function it_can_delete_a_profile()
    {
        $shippingProfileId = $this->shippingProfileApplication->createShippingProfile(new CreateShippingProfile(
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $this->shippingProfileApplication->deleteShippingProfile(new DeleteShippingProfile($shippingProfileId->get()));

        $this->assertEquals([
            new ShippingProfileDeleted($shippingProfileId),
        ], $this->eventDispatcher->releaseDispatchedEvents());

        $this->expectException(CouldNotFindShippingProfile::class);
        $this->shippingProfileRepository->find($shippingProfileId);
    }

    /** @test */
    public function it_can_delete_a_tariff()
    {
        $shippingProfileId = $this->shippingProfileApplication->createShippingProfile(new CreateShippingProfile(
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $tariffId = $this->shippingProfileApplication->createTariff(new CreateTariff($shippingProfileId->get(), '50', '10', '30'));

        $this->shippingProfileApplication->deleteTariff(new DeleteTariff($shippingProfileId->get(), $tariffId->get()));

        $this->assertEquals([
            new TariffDeleted($shippingProfileId, $tariffId),
        ], $this->eventDispatcher->releaseDispatchedEvents());

        $shippingProfile = $this->shippingProfileRepository->find($shippingProfileId);
        $this->assertCount(0, $shippingProfile->getTariffs());
    }
}
