<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

class CreateVatRate
{
    private string $countryId;
    private string $rate;
    private array $data;

    public function __construct(string $countryId, string $rate, array $data)
    {
        $this->countryId = $countryId;
        $this->rate = $rate;
        $this->data = $data;
    }

    public function getCountryId(): CountryId
    {
        return CountryId::fromString($this->countryId);
    }

    public function getRate(): TaxRate
    {
        return TaxRate::fromString($this->rate);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
