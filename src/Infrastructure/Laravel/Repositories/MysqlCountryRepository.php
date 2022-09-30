<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Application\Country\BillingCountryRepository;
use Thinktomorrow\Trader\Domain\Model\Country\Country;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Country\CountryRepository;
use Thinktomorrow\Trader\Domain\Model\Country\Exceptions\CouldNotFindCountry;

class MysqlCountryRepository implements CountryRepository, BillingCountryRepository
{
    private static $countryTable = 'trader_countries';

    public function save(Country $country): void
    {
        $state = $country->getMappedData();

        if (! $this->exists($country->countryId)) {
            DB::table(static::$countryTable)->insert($state);
        } else {
            DB::table(static::$countryTable)->where('country_id', $country->countryId->get())->update($state);
        }
    }

    private function exists(CountryId $countryId): bool
    {
        return DB::table(static::$countryTable)->where('country_id', $countryId->get())->exists();
    }

    public function find(CountryId $countryId): Country
    {
        $countryState = DB::table(static::$countryTable)
            ->where(static::$countryTable . '.country_id', $countryId->get())
            ->first();

        if (! $countryState) {
            throw new CouldNotFindCountry('No country found by id [' . $countryId->get() . ']');
        }

        return Country::fromMappedData((array)$countryState);
    }

    public function delete(CountryId $countryId): void
    {
        DB::table(static::$countryTable)->where('country_id', $countryId->get())->delete();
    }

    public function getAvailableBillingCountries(): iterable
    {
        $countryStates = DB::table(static::$countryTable)
            ->where('active', '1')
            ->orderBy('order_column')
            ->get()
            ->map(fn ($item) => (array)$item)
            ->toArray();

        return array_map(fn ($countryState) => \Thinktomorrow\Trader\Application\Country\Country::fromMappedData($countryState), $countryStates);
    }

    public function findBillingCountry(CountryId $countryId): \Thinktomorrow\Trader\Application\Country\Country
    {
        $countryState = DB::table(static::$countryTable)
            ->where('country_id', $countryId->get())
            ->first();

        return \Thinktomorrow\Trader\Application\Country\Country::fromMappedData((array) $countryState);
    }
}
