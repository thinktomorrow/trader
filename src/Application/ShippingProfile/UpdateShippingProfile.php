<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\ShippingProfile;

use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;

class UpdateShippingProfile
{
    private string $shippingProfileId;
    private array $countryIds;
    private array $data;

    public function __construct(string $shippingProfileId, array $countryIds, array $data)
    {
        $this->shippingProfileId = $shippingProfileId;
        $this->countryIds = $countryIds;
        $this->data = $data;
    }

    public function getShippingProfileId(): ShippingProfileId
    {
        return ShippingProfileId::fromString($this->shippingProfileId);
    }

    public function getCountryIds(): array
    {
        return array_map(fn($country) => CountryId::fromString($country), $this->countryIds);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
