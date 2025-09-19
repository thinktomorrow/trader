<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Application\Country\BillingCountryRepository;
use Thinktomorrow\Trader\Domain\Model\Country\Country;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Country\CountryRepository;
use Thinktomorrow\Trader\Domain\Model\Country\Exceptions\CouldNotFindCountry;

class InMemoryCountryRepository implements CountryRepository, BillingCountryRepository, InMemoryRepository
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

    public function getAvailableBillingCountries(): iterable
    {
        return array_values(array_map(fn ($country) => \Thinktomorrow\Trader\Application\Country\Country::fromMappedData($country->getMappedData()), static::$countries));
    }

    public function findBillingCountry(CountryId $countryId): \Thinktomorrow\Trader\Application\Country\Country
    {
        foreach (static::$countries as $country) {
            if ($country->countryId->equals($countryId)) {
                return \Thinktomorrow\Trader\Application\Country\Country::fromMappedData($country->getMappedData());
            }
        }
    }
}
