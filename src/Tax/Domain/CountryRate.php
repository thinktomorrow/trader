<?php

namespace Thinktomorrow\Trader\Tax\Domain;

use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Countries\CountryId;

class CountryRate
{
    /**
     * @var CountryId
     */
    private $countryId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Percentage
     */
    private $percentage;

    public function __construct(string $name, Percentage $percentage, CountryId $countryId)
    {
        $this->name = $name;
        $this->percentage = $percentage;
        $this->countryId = $countryId;
    }

    public function get(): Percentage
    {
        return $this->percentage;
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
