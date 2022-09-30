<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Country;

use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

interface BillingCountryRepository
{
    /** @return Country[] */
    public function getAvailableBillingCountries(): iterable;

    public function findBillingCountry(CountryId $countryId): Country;
}
