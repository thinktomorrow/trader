<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Country;

interface CountryRepository
{
    public function save(Country $customer): void;

    public function find(CountryId $customerId): Country;

    public function delete(CountryId $customerId): void;
}
