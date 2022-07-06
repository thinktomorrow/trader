<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\ShippingProfile;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\TariffId;

class UpdateTariff
{
    private string $tariffId;
    private string $shippingProfileId;
    private string $rate;
    private string $from;
    private ?string $to;

    public function __construct(string $tariffId, string $shippingProfileId, string $rate, string $from, ?string $to)
    {
        $this->tariffId = $tariffId;
        $this->shippingProfileId = $shippingProfileId;
        $this->rate = $rate;
        $this->from = $from;
        $this->to = $to;
    }

    public function getTariffId(): TariffId
    {
        return TariffId::fromString($this->tariffId);
    }

    public function getShippingProfileId(): ShippingProfileId
    {
        return ShippingProfileId::fromString($this->shippingProfileId);
    }

    public function getRate(): Money
    {
        return Cash::make($this->rate);
    }

    public function getFrom(): Money
    {
        return Cash::make($this->from);
    }

    public function getTo(): ?Money
    {
        return $this->to ? Cash::make($this->to) : null;
    }
}
