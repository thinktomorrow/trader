<?php

namespace Thinktomorrow\Trader\Tax\Domain;

use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Countries\CountryId;

class CountryRate
{
    private $standardRate;

    /**
     * @var CountryId
     */
    private $countryId;

    public function __construct(string $name, Percentage $percentage, CountryId $countryId)
    {
        $this->standardRate = new StandardRate($name, $percentage);
        $this->countryId = $countryId;
    }

    public function get(): Percentage
    {
        return $this->standardRate->get();
    }

    public function name()
    {
        return $this->name;
    }

    public function matchesCountry(CountryId $countryId): bool
    {
        return $this->countryId->equals($countryId);
    }
}