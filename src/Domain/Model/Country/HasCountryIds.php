<?php

namespace Thinktomorrow\Trader\Domain\Model\Country;

use Assert\Assertion;

trait HasCountryIds
{
    /** @var CountryId[] */
    protected array $countryIds = [];

    public function hasCountry(CountryId $countryId): bool
    {
        /** @var CountryId $existingCountryId */
        foreach ($this->countryIds as $existingCountryId) {
            if ($existingCountryId->equals($countryId)) {
                return true;
            }
        }

        return false;
    }

    public function addCountry(CountryId $countryId): void
    {
        $this->countryIds[] = $countryId;
    }

    public function deleteCountry(CountryId $countryId): void
    {
        foreach ($this->countryIds as $index => $existingCountryId) {
            if ($countryId->equals($existingCountryId)) {
                unset($this->countryIds[$index]);
                $this->countryIds = array_values($this->countryIds);
            }
        }
    }

    public function updateCountries(array $countryIds): void
    {
        Assertion::allIsInstanceOf($countryIds, CountryId::class);

        $this->countryIds = $countryIds;
    }

    public function getCountryIds(): array
    {
        return $this->countryIds;
    }

    public function hasAnyCountries(): bool
    {
        return count($this->countryIds) > 0;
    }
}
