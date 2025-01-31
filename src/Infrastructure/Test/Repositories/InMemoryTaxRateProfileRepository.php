<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\Exceptions\CouldNotFindTaxRateProfile;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateDoubleId;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfile;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileId;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileRepository;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileState;

final class InMemoryTaxRateProfileRepository implements TaxRateProfileRepository
{
    /** @var TaxRateProfile[] */
    private static array $taxRateProfiles = [];

    private string $nextReference = 'yyy-123';
    private $nextTaxRateDoubleReference = 'zzz-123';

    public function save(TaxRateProfile $taxRateProfile): void
    {
        static::$taxRateProfiles[$taxRateProfile->taxRateProfileId->get()] = $taxRateProfile;
    }

    public function find(TaxRateProfileId $taxRateProfileId): TaxRateProfile
    {
        if (! isset(static::$taxRateProfiles[$taxRateProfileId->get()])) {
            throw new CouldNotFindTaxRateProfile('No taxRate found by id ' . $taxRateProfileId);
        }

        return static::$taxRateProfiles[$taxRateProfileId->get()];
    }

    public function delete(TaxRateProfileId $taxRateProfileId): void
    {
        if (! isset(static::$taxRateProfiles[$taxRateProfileId->get()])) {
            throw new CouldNotFindTaxRateProfile('No available taxRate found by id ' . $taxRateProfileId);
        }

        unset(static::$taxRateProfiles[$taxRateProfileId->get()]);
    }

    public function nextReference(): TaxRateProfileId
    {
        return TaxRateProfileId::fromString($this->nextReference);
    }

    // For testing purposes only
    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public static function clear()
    {
        static::$taxRateProfiles = [];
    }

    public function nextTaxRateDoubleReference(): TaxRateDoubleId
    {
        return TaxRateDoubleId::fromString($this->nextTaxRateDoubleReference);
    }

    public function findTaxRateProfileForCountry(string $countryId): ?TaxRateProfile
    {
        foreach (static::$taxRateProfiles as $taxRateProfile) {
            if ($taxRateProfile->getState() == TaxRateProfileState::online && $taxRateProfile->hasCountry(CountryId::fromString($countryId))) {
                return $taxRateProfile;
            }
        }

        return null;
    }
}
