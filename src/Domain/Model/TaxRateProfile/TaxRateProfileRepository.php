<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\TaxRateProfile;

interface TaxRateProfileRepository
{
    public function save(TaxRateProfile $taxRateProfile): void;

    public function find(TaxRateProfileId $taxRateProfileId): TaxRateProfile;

    public function delete(TaxRateProfileId $taxRateProfileId): void;

    public function nextReference(): TaxRateProfileId;

    public function nextTaxRateDoubleReference(): TaxRateDoubleId;

    public function findTaxRateProfileForCountry(string $countryId): ?TaxRateProfile;
}
