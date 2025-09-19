<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Application\Cart\ShippingProfile\ShippingProfileForCartRepository;
use Thinktomorrow\Trader\Application\Country\ShippingCountryRepository;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\CouldNotFindShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileState;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\TariffId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultShippingProfileForCart;

final class InMemoryShippingProfileRepository implements ShippingProfileRepository, ShippingProfileForCartRepository, ShippingCountryRepository, InMemoryRepository
{
    /** @var ShippingProfile[] */
    private static array $shippingProfiles = [];

    private string $nextReference = 'sss-123';
    private $nextTariffReference = 'ttt-123';

    public function save(ShippingProfile $shippingProfile): void
    {
        static::$shippingProfiles[$shippingProfile->shippingProfileId->get()] = $shippingProfile;
    }

    public function find(ShippingProfileId $shippingProfileId): ShippingProfile
    {
        if (! isset(static::$shippingProfiles[$shippingProfileId->get()])) {
            throw new CouldNotFindShippingProfile('No shipping found by id ' . $shippingProfileId);
        }

        return static::$shippingProfiles[$shippingProfileId->get()];
    }

    public function delete(ShippingProfileId $shippingProfileId): void
    {
        if (! isset(static::$shippingProfiles[$shippingProfileId->get()])) {
            throw new CouldNotFindShippingProfile('No available shipping found by id ' . $shippingProfileId);
        }

        unset(static::$shippingProfiles[$shippingProfileId->get()]);
    }

    public function nextReference(): ShippingProfileId
    {
        return ShippingProfileId::fromString($this->nextReference);
    }

    // For testing purposes only
    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public static function clear()
    {
        static::$shippingProfiles = [];
    }

    public function getAvailableShippingCountries(): iterable
    {
        $result = [];
        $countryIds = [];

        foreach (static::$shippingProfiles as $shippingProfile) {
            $countryIds = array_merge($countryIds, $shippingProfile->getCountryIds());
        }

        foreach (InMemoryCountryRepository::$countries as $country) {
            if (in_array($country->countryId, $countryIds)) {
                $result[] = \Thinktomorrow\Trader\Application\Country\Country::fromMappedData($country->getMappedData());
            }
        }

        return $result;
    }

    public function nextTariffReference(): TariffId
    {
        return TariffId::fromString($this->nextTariffReference);
    }

    public function findAllShippingProfilesForCart(?string $countryId = null): array
    {
        $activeProfiles = [];

        foreach (static::$shippingProfiles as $shippingProfile) {
            if ($shippingProfile->getState() == ShippingProfileState::online && (! $countryId || $shippingProfile->hasCountry(CountryId::fromString($countryId)))) {
                $activeProfiles[] = $shippingProfile;
            }
        }

        return array_map(fn ($shippingProfile) => DefaultShippingProfileForCart::fromMappedData($shippingProfile->getMappedData()), $activeProfiles);
    }
}
