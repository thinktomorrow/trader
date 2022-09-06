<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Country;

interface CountryRepository
{
    public function save(Country $country): void;

    public function find(CountryId $countryId): Country;

    public function delete(CountryId $countryId): void;
}
