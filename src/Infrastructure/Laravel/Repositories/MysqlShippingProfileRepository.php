<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\ShippingProfileForCart;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\ShippingProfileForCartRepository;
use Thinktomorrow\Trader\Application\Country\ShippingCountryRepository;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\CouldNotFindShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileState;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Tariff;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\TariffId;

class MysqlShippingProfileRepository implements ShippingProfileRepository, ShippingProfileForCartRepository, ShippingCountryRepository
{
    private static $shippingProfileTable = 'trader_shipping_profiles';
    private static $shippingProfileTariffTable = 'trader_shipping_profile_tariffs';
    private static $shippingProfileCountryTable = 'trader_shipping_profile_countries';
    private static $countryTable = 'trader_countries';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function save(ShippingProfile $shippingProfile): void
    {
        $state = $shippingProfile->getMappedData();

        if (! $this->exists($shippingProfile->shippingProfileId)) {
            DB::table(static::$shippingProfileTable)->insert($state);
        } else {
            DB::table(static::$shippingProfileTable)->where('shipping_profile_id', $shippingProfile->shippingProfileId->get())->update($state);
        }

        $this->upsertTariffs($shippingProfile);
        $this->upsertCountryIds($shippingProfile);
    }

    private function upsertTariffs(ShippingProfile $shippingProfile): void
    {
        DB::table(static::$shippingProfileTariffTable)
            ->where('shipping_profile_id', $shippingProfile->shippingProfileId->get())
            ->delete();

        DB::table(static::$shippingProfileTariffTable)
            ->insert($shippingProfile->getChildEntities()[Tariff::class]);
    }

    private function upsertCountryIds(ShippingProfile $shippingProfile): void
    {
        DB::table(static::$shippingProfileCountryTable)
            ->where('shipping_profile_id', $shippingProfile->shippingProfileId->get())
            ->delete();

        DB::table(static::$shippingProfileCountryTable)
            ->insert(array_map(fn (string $countryId) => [
                'shipping_profile_id' => $shippingProfile->shippingProfileId->get(),
                'country_id' => $countryId,
            ], $shippingProfile->getChildEntities()[CountryId::class]));
    }

    private function exists(ShippingProfileId $shippingProfileId): bool
    {
        return DB::table(static::$shippingProfileTable)->where('shipping_profile_id', $shippingProfileId->get())->exists();
    }

    public function find(ShippingProfileId $shippingProfileId): ShippingProfile
    {
        $shippingProfileState = DB::table(static::$shippingProfileTable)
            ->where(static::$shippingProfileTable . '.shipping_profile_id', $shippingProfileId->get())
            ->first();

        if (! $shippingProfileState) {
            throw new CouldNotFindShippingProfile('No shipping profile found by id [' . $shippingProfileId->get() . ']');
        }

        $tariffStates = DB::table(static::$shippingProfileTariffTable)
            ->where(static::$shippingProfileTariffTable . '.shipping_profile_id', $shippingProfileId->get())
            ->get()
            ->map(fn ($item) => (array)$item)
            ->toArray();

        $countryStates = DB::table(static::$shippingProfileCountryTable)
            ->join(static::$countryTable, static::$shippingProfileCountryTable.'.country_id', '=', static::$countryTable.'.country_id')
            ->where(static::$shippingProfileCountryTable . '.shipping_profile_id', $shippingProfileId->get())
            ->where(static::$countryTable . '.active', '1')
            ->select(static::$countryTable.'.country_id')
            ->get()
            ->map(fn ($item) => (array)$item)
            ->toArray();



        return ShippingProfile::fromMappedData(array_merge((array)$shippingProfileState, [
            'requires_address' => (bool) $shippingProfileState->requires_address,
        ]), [
            Tariff::class => $tariffStates,
            CountryId::class => $countryStates,
        ]);
    }

    public function delete(ShippingProfileId $shippingProfileId): void
    {
        DB::table(static::$shippingProfileTable)->where('shipping_profile_id', $shippingProfileId->get())->delete();
    }

    public function nextReference(): ShippingProfileId
    {
        return ShippingProfileId::fromString((string)Uuid::uuid4());
    }

    public function nextTariffReference(): TariffId
    {
        return TariffId::fromString((string)Uuid::uuid4());
    }

    public function getAvailableShippingCountries(): iterable
    {
        $countryStates = DB::table(static::$countryTable)
            ->join(static::$shippingProfileCountryTable, static::$countryTable.'.country_id', '=', static::$shippingProfileCountryTable.'.country_id')
            ->where(static::$countryTable . '.active', '1')
            ->groupBy(static::$countryTable.'.country_id')
            ->select(static::$countryTable.'.*')
            ->orderBy(static::$countryTable.'.order_column')
            ->get()
            ->map(fn ($item) => (array)$item)
            ->toArray();

        return array_map(fn ($countryState) => \Thinktomorrow\Trader\Application\Country\Country::fromMappedData($countryState), $countryStates);
    }

    public function findAllShippingProfilesForCart(?string $countryId = null): array
    {
        $builder = DB::table(static::$shippingProfileTable)
            ->whereIn('state', ShippingProfileState::onlineStates())
            ->orderBy('order_column', 'ASC');

        if ($countryId) {
            $builder->leftJoin(static::$shippingProfileCountryTable, static::$shippingProfileTable.'.shipping_profile_id', '=', static::$shippingProfileCountryTable.'.shipping_profile_id')
                ->where(static::$shippingProfileCountryTable . '.country_id', $countryId)
                ->select(static::$shippingProfileTable.'.*');
        }

        return $builder
            ->get()
            ->map(fn ($shippingProfileState) => $this->container->get(ShippingProfileForCart::class)::fromMappedData(array_merge((array)$shippingProfileState, [
                'requires_address' => (bool) $shippingProfileState->requires_address,
            ])))
            ->toArray();
    }
}
