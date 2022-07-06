<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\ShippingProfile;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;

class CreateTariff
{
    private string $shippingProfileId;
    private string $rate;
    private string $from;
    private ?string $to;

    public function __construct(string $shippingProfileId, string $rate, string $from, ?string $to)
    {
        $this->shippingProfileId = $shippingProfileId;
        $this->rate = $rate;
        $this->from = $from;
        $this->to = $to;
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
