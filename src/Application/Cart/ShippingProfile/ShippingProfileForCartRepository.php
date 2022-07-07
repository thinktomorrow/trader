<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\ShippingProfile;

interface ShippingProfileForCartRepository
{
    /** @return ShippingProfileForCart[] */
    public function findAllShippingProfilesForCart(?string $countryId = null): array;
}
