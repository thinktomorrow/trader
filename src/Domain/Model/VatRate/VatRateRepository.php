<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\VatRate;

use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

interface VatRateRepository
{
    public function save(VatRate $vatRate): void;

    public function find(VatRateId $vatRateId): VatRate;

    public function delete(VatRateId $vatRateId): void;

    public function nextReference(): VatRateId;

    public function nextBaseRateReference(): BaseRateId;

    public function getVatRatesForCountry(CountryId $countryId): array;

    public function findStandardVatRateForCountry(CountryId $countryId): ?VatRate;

    public function getPrimaryVatRates(): array;

    public function getStandardPrimaryVatRate(): VatPercentage;
}
