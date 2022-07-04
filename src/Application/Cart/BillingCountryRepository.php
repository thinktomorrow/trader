<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\Domain\Model\Country\Country;

interface BillingCountryRepository
{
    /** @return Country[] */
    public function getAvailableBillingCountries(): iterable;
}
