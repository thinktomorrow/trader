<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;

class ChangeStandardVatRateForCountry
{
    private string $countryId;
    private string $vatRateId;

    public function __construct(string $countryId, string $vatRateId)
    {
        $this->vatRateId = $vatRateId;
        $this->countryId = $countryId;
    }

    public function getCountryId(): CountryId
    {
        return CountryId::fromString($this->countryId);
    }

    public function getVatRateId(): VatRateId
    {
        return VatRateId::fromString($this->vatRateId);
    }
}
