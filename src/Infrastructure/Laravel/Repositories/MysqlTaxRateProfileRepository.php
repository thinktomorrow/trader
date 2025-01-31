<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\Exceptions\CouldNotFindTaxRateProfile;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfile;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileId;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileRepository;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileState;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateDouble;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateDoubleId;

class MysqlTaxRateProfileRepository implements TaxRateProfileRepository
{
    private static $taxRateProfileTable = 'trader_taxrate_profiles';
    private static $taxRateProfileTaxRateDoubleTable = 'trader_taxrate_profile_doubles';
    private static $taxRateProfileCountryTable = 'trader_taxrate_profile_countries';
    private static $countryTable = 'trader_countries';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function save(TaxRateProfile $taxRateProfile): void
    {
        $state = $taxRateProfile->getMappedData();

        if (! $this->exists($taxRateProfile->taxRateProfileId)) {
            DB::table(static::$taxRateProfileTable)->insert($state);
        } else {
            DB::table(static::$taxRateProfileTable)->where('taxrate_profile_id', $taxRateProfile->taxRateProfileId->get())->update($state);
        }

        $this->upsertTaxRateDoubles($taxRateProfile);
        $this->upsertCountryIds($taxRateProfile);
    }

    private function upsertTaxRateDoubles(TaxRateProfile $taxRateProfile): void
    {
        DB::table(static::$taxRateProfileTaxRateDoubleTable)
            ->where('taxrate_profile_id', $taxRateProfile->taxRateProfileId->get())
            ->delete();

        DB::table(static::$taxRateProfileTaxRateDoubleTable)
            ->insert($taxRateProfile->getChildEntities()[TaxRateDouble::class]);
    }

    private function upsertCountryIds(TaxRateProfile $taxRateProfile): void
    {
        DB::table(static::$taxRateProfileCountryTable)
            ->where('taxrate_profile_id', $taxRateProfile->taxRateProfileId->get())
            ->delete();

        DB::table(static::$taxRateProfileCountryTable)
            ->insert(array_map(fn (string $countryId) => [
                'taxrate_profile_id' => $taxRateProfile->taxRateProfileId->get(),
                'country_id' => $countryId,
            ], $taxRateProfile->getChildEntities()[CountryId::class]));
    }

    private function exists(TaxRateProfileId $taxRateProfileId): bool
    {
        return DB::table(static::$taxRateProfileTable)->where('taxrate_profile_id', $taxRateProfileId->get())->exists();
    }

    public function find(TaxRateProfileId $taxRateProfileId): TaxRateProfile
    {
        $taxRateProfileState = DB::table(static::$taxRateProfileTable)
            ->where(static::$taxRateProfileTable . '.taxrate_profile_id', $taxRateProfileId->get())
            ->first();

        if (! $taxRateProfileState) {
            throw new CouldNotFindTaxRateProfile('No taxRate profile found by id [' . $taxRateProfileId->get() . ']');
        }

        return $this->makeWithChildEntities($taxRateProfileId, $taxRateProfileState);
    }

    public function delete(TaxRateProfileId $taxRateProfileId): void
    {
        DB::table(static::$taxRateProfileTable)->where('taxrate_profile_id', $taxRateProfileId->get())->delete();
    }

    public function nextReference(): TaxRateProfileId
    {
        return TaxRateProfileId::fromString((string)Uuid::uuid4());
    }

    public function nextTaxRateDoubleReference(): TaxRateDoubleId
    {
        return TaxRateDoubleId::fromString((string)Uuid::uuid4());
    }

    public function findTaxRateProfileForCountry(string $countryId): ?TaxRateProfile
    {
        $result = DB::table(static::$taxRateProfileTable)
            ->whereIn('state', TaxRateProfileState::onlineStates())
            ->orderBy('order_column', 'ASC')
            ->leftJoin(static::$taxRateProfileCountryTable, static::$taxRateProfileTable.'.taxrate_profile_id', '=', static::$taxRateProfileCountryTable.'.taxrate_profile_id')
            ->where(static::$taxRateProfileCountryTable . '.country_id', $countryId)
            ->select(static::$taxRateProfileTable.'.*')
            ->first();

        if(!$result) {
            return null;
        }

        return $this->makeWithChildEntities(TaxRateProfileId::fromString($result->taxrate_profile_id), $result);
    }

    private function makeWithChildEntities(TaxRateProfileId $taxRateProfileId, $taxRateProfileState): TaxRateProfile
    {
        $taxRateDoubleStates = DB::table(static::$taxRateProfileTaxRateDoubleTable)
            ->where(static::$taxRateProfileTaxRateDoubleTable . '.taxrate_profile_id', $taxRateProfileId->get())
            ->get()
            ->map(fn ($item) => (array)$item)
            ->toArray();

        $countryStates = DB::table(static::$taxRateProfileCountryTable)
            ->join(static::$countryTable, static::$taxRateProfileCountryTable.'.country_id', '=', static::$countryTable.'.country_id')
            ->where(static::$taxRateProfileCountryTable . '.taxrate_profile_id', $taxRateProfileId->get())
            ->where(static::$countryTable . '.active', '1')
            ->select(static::$countryTable.'.country_id')
            ->get()
            ->map(fn ($item) => (array)$item)
            ->toArray();

        return TaxRateProfile::fromMappedData((array)$taxRateProfileState, [
            TaxRateDouble::class => $taxRateDoubleStates,
            CountryId::class => $countryStates,
        ]);
    }
}
