<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\ShippingProfile;

use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

class CreateShippingProfile
{
    private bool $requiresAddress;
    private array $data;
    private array $countryIds;

    public function __construct(bool $requiresAddress, array $countryIds, array $data)
    {
        $this->requiresAddress = $requiresAddress;
        $this->data = $data;
        $this->countryIds = $countryIds;
    }

    public function requiresAddress(): bool
    {
        return $this->requiresAddress;
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
