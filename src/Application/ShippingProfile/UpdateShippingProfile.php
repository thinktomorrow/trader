<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\ShippingProfile;

use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProviderId;

class UpdateShippingProfile
{
    private string $shippingProfileId;
    private string $providerId;
    private bool $requiresAddress;
    private array $countryIds;
    private array $data;

    public function __construct(string $shippingProfileId, string $providerId, bool $requiresAddress, array $countryIds, array $data)
    {
        $this->shippingProfileId = $shippingProfileId;
        $this->providerId = $providerId;
        $this->requiresAddress = $requiresAddress;
        $this->countryIds = $countryIds;
        $this->data = $data;
    }

    public function getShippingProfileId(): ShippingProfileId
    {
        return ShippingProfileId::fromString($this->shippingProfileId);
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
