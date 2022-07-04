<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\Domain\Model\Country\Country;

interface ShippingCountryRepository
{
    /** @return Country[] */
    public function getAvailableShippingCountries(): iterable;
}
