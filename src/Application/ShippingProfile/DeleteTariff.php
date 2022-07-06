<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\ShippingProfile;

use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\TariffId;

class DeleteTariff
{
    private string $shippingProfileId;
    private string $tariffId;

    public function __construct(string $shippingProfileId, string $tariffId)
    {
        $this->shippingProfileId = $shippingProfileId;
        $this->tariffId = $tariffId;
    }

    public function getShippingProfileId(): ShippingProfileId
    {
        return ShippingProfileId::fromString($this->shippingProfileId);
    }

    public function getTariffId(): TariffId
    {
        return TariffId::fromString($this->tariffId);
    }
}
