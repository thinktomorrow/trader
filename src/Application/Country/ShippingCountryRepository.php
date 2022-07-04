<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Country;

interface ShippingCountryRepository
{
    /** @return Country[] */
    public function getAvailableShippingCountries(): iterable;
}
