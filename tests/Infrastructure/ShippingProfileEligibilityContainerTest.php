<?php

declare(strict_types=1);

namespace Tests\Infrastructure;

use Thinktomorrow\Trader\Application\Cart\ShippingProfile\AvailableShippingProfilesForOrder;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\Eligibility\ShippingProfileEligibility;
use Thinktomorrow\Trader\Application\Taxon\Queries\TaxonHierarchy;

final class ShippingProfileEligibilityContainerTest extends TestCase
{
    public function test_shipping_profile_eligibility_services_are_resolvable(): void
    {
        $this->assertInstanceOf(
            ShippingProfileEligibility::class,
            $this->app->make(ShippingProfileEligibility::class)
        );
        $this->assertInstanceOf(
            AvailableShippingProfilesForOrder::class,
            $this->app->make(AvailableShippingProfilesForOrder::class)
        );
        $this->assertInstanceOf(
            TaxonHierarchy::class,
            $this->app->make(TaxonHierarchy::class)
        );
    }
}
