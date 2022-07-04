<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Domain\Model\Country\Country;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Country\CountryRepository;
use Thinktomorrow\Trader\Domain\Model\Country\Exceptions\CouldNotFindCountry;

class InMemoryCountryRepository implements CountryRepository
{
    public static array $countries = [];

    public function save(Country $country): void
    {
        static::$countries[$country->countryId->get()] = $country;
    }

    public function find(CountryId $countryId): Country
    {
        if (! isset(static::$countries[$countryId->get()])) {
            throw new CouldNotFindCountry('No country found by id ' . $countryId->get());
        }

        return static::$countries[$countryId->get()];
    }

    public function delete(CountryId $countryId): void
    {
        if (! isset(static::$countries[$countryId->get()])) {
            throw new CouldNotFindCountry('No available country found by id ' . $countryId->get());
        }

        unset(static::$countries[$countryId->get()]);
    }

    public static function clear()
    {
        static::$countries = [];
    }
}
