<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatRate\Exceptions\CouldNotFindVatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateMappingId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateRepository;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateState;

final class InMemoryVatRateRepository implements VatRateRepository
{
    /** @var VatRate[] */
    private static array $taxRateProfiles = [];

    private string $nextReference = 'yyy-123';
    private $nextTaxRateDoubleReference = 'zzz-123';

    public function save(VatRate $taxRateProfile): void
    {
        static::$taxRateProfiles[$taxRateProfile->taxRateProfileId->get()] = $taxRateProfile;
    }

    public function find(VatRateId $taxRateProfileId): VatRate
    {
        if (! isset(static::$taxRateProfiles[$taxRateProfileId->get()])) {
            throw new CouldNotFindVatRate('No taxRate found by id ' . $taxRateProfileId);
        }

        return static::$taxRateProfiles[$taxRateProfileId->get()];
    }

    public function delete(VatRateId $taxRateProfileId): void
    {
        if (! isset(static::$taxRateProfiles[$taxRateProfileId->get()])) {
            throw new CouldNotFindVatRate('No available taxRate found by id ' . $taxRateProfileId);
        }

        unset(static::$taxRateProfiles[$taxRateProfileId->get()]);
    }

    public function nextReference(): VatRateId
    {
        return VatRateId::fromString($this->nextReference);
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

    public function nextVatRateMappingReference(): VatRateMappingId
    {
        return VatRateMappingId::fromString($this->nextTaxRateDoubleReference);
    }

    public function findVatRateForCountry(string $countryId): ?VatRate
    {
        foreach (static::$taxRateProfiles as $taxRateProfile) {
            if ($taxRateProfile->getState() == VatRateState::online && $taxRateProfile->hasCountry(CountryId::fromString($countryId))) {
                return $taxRateProfile;
            }
        }

        return null;
    }
}
