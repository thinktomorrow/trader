<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Country;

interface BillingCountryRepository
{
    /** @return Country[] */
    public function getAvailableBillingCountries(): iterable;
}
