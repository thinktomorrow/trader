<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\TaxRateProfile;

use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

class CreateTaxRateProfile
{
    private array $countryIds;
    private array $data;

    public function __construct(array $countryIds, array $data)
    {
        $this->countryIds = $countryIds;
        $this->data = $data;
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
