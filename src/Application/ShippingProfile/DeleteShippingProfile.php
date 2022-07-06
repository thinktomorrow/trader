<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\ShippingProfile;

use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;

class DeleteShippingProfile
{
    private string $shippingProfileId;

    public function __construct(string $shippingProfileId)
    {
        $this->shippingProfileId = $shippingProfileId;
    }

    public function getShippingProfileId(): ShippingProfileId
    {
        return ShippingProfileId::fromString($this->shippingProfileId);
    }
}
