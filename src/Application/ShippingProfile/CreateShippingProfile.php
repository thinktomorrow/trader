<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\ShippingProfile;

use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProviderId;

class CreateShippingProfile
{
    private string $providerId;
    private bool $requiresAddress;
    private array $data;
    private array $countryIds;

    public function __construct(string $providerId, bool $requiresAddress, array $countryIds, array $data)
    {
        $this->providerId = $providerId;
        $this->requiresAddress = $requiresAddress;
        $this->data = $data;
        $this->countryIds = $countryIds;
    }

    public function getProviderId(): ShippingProviderId
    {
        return ShippingProviderId::fromString($this->providerId);
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
