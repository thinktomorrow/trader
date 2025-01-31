<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\TaxRateProfile;

use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileId;

class UpdateTaxRateProfile
{
    private string $taxRateProfileId;
    private array $countryIds;
    private array $data;

    public function __construct(string $taxRateProfileId, array $countryIds, array $data)
    {
        $this->taxRateProfileId = $taxRateProfileId;
        $this->countryIds = $countryIds;
        $this->data = $data;
    }

    public function getTaxRateProfileId(): TaxRateProfileId
    {
        return TaxRateProfileId::fromString($this->taxRateProfileId);
    }

    public function getCountryIds(): array
    {
        return array_map(fn ($country) => CountryId::fromString($country), $this->countryIds);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
