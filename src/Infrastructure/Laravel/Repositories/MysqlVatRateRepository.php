<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatRate\Exceptions\CouldNotFindVatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateRepository;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateState;

class MysqlVatRateRepository implements VatRateRepository
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

    public function save(VatRate $taxRateProfile): void
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

    private function upsertTaxRateDoubles(VatRate $taxRateProfile): void
    {
        DB::table(static::$taxRateProfileTaxRateDoubleTable)
            ->where('taxrate_profile_id', $taxRateProfile->taxRateProfileId->get())
            ->delete();

        DB::table(static::$taxRateProfileTaxRateDoubleTable)
            ->insert($taxRateProfile->getChildEntities()[BaseRate::class]);
    }

    private function upsertCountryIds(VatRate $taxRateProfile): void
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

    private function exists(VatRateId $taxRateProfileId): bool
    {
        return DB::table(static::$taxRateProfileTable)->where('taxrate_profile_id', $taxRateProfileId->get())->exists();
    }

    public function find(VatRateId $taxRateProfileId): VatRate
    {
        $taxRateProfileState = DB::table(static::$taxRateProfileTable)
            ->where(static::$taxRateProfileTable . '.taxrate_profile_id', $taxRateProfileId->get())
            ->first();

        if (! $taxRateProfileState) {
            throw new CouldNotFindVatRate('No taxRate profile found by id [' . $taxRateProfileId->get() . ']');
        }

        return $this->makeWithChildEntities($taxRateProfileId, $taxRateProfileState);
    }

    public function delete(VatRateId $taxRateProfileId): void
    {
        DB::table(static::$taxRateProfileTable)->where('taxrate_profile_id', $taxRateProfileId->get())->delete();
    }

    public function nextReference(): VatRateId
    {
        return VatRateId::fromString((string)Uuid::uuid4());
    }

    public function nextVatRateMappingReference(): BaseRateId
    {
        return BaseRateId::fromString((string)Uuid::uuid4());
    }

    public function findVatRateForCountry(string $countryId): ?VatRate
    {
        $result = DB::table(static::$taxRateProfileTable)
            ->whereIn('state', VatRateState::onlineStates())
            ->orderBy('order_column', 'ASC')
            ->leftJoin(static::$taxRateProfileCountryTable, static::$taxRateProfileTable.'.taxrate_profile_id', '=', static::$taxRateProfileCountryTable.'.taxrate_profile_id')
            ->where(static::$taxRateProfileCountryTable . '.country_id', $countryId)
            ->select(static::$taxRateProfileTable.'.*')
            ->first();

        if (! $result) {
            return null;
        }

        return $this->makeWithChildEntities(VatRateId::fromString($result->taxrate_profile_id), $result);
    }

    private function makeWithChildEntities(VatRateId $taxRateProfileId, $taxRateProfileState): VatRate
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

        return VatRate::fromMappedData((array)$taxRateProfileState, [
            BaseRate::class => $taxRateDoubleStates,
            CountryId::class => $countryStates,
        ]);
    }
}
