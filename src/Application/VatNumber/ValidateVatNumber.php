<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatNumber;

use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

class ValidateVatNumber
{
    private string $countryId;
    private string $vatNumber;

    public function __construct(string $countryId, string $vatNumber)
    {
        $this->countryId = $countryId;
        $this->vatNumber = $vatNumber;
    }

    public function getVatNumber(): string
    {
        return $this->vatNumber;
    }

    public function getCountryId(): CountryId
    {
        return CountryId::fromString($this->countryId);
    }
}
