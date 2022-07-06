<?php
declare(strict_types=1);

namespace Tests\Acceptance\ShippingProfile;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\ShippingProfile\ShippingProfileApplication;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryShippingProfileRepository;

class ShippingProfileContext extends TestCase
{
    protected ShippingProfileApplication $shippingProfileApplication;
    protected InMemoryShippingProfileRepository $shippingProfileRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shippingProfileApplication = new ShippingProfileApplication(
            $this->eventDispatcher = new EventDispatcherSpy(),
            $this->shippingProfileRepository = new InMemoryShippingProfileRepository(),
        );
    }

    public function tearDown(): void
    {
        $this->shippingProfileRepository->clear();
    }
}
