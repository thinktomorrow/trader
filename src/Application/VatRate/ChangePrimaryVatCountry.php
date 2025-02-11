<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

class ChangePrimaryVatCountry
{
    private string $countryId;

    public function __construct(string $countryId)
    {
        $this->countryId = $countryId;
    }

    public function getCountryId(): CountryId
    {
        return CountryId::fromString($this->countryId);
    }
}
