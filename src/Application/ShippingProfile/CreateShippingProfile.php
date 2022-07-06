<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\ShippingProfile;

use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

class CreateShippingProfile
{
    private array $data;
    private array $countryIds;

    public function __construct(array $countryIds, array $data)
    {
        $this->data = $data;
        $this->countryIds = $countryIds;
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
