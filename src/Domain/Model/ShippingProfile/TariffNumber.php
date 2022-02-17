<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\ShippingProfile;

class TariffNumber
{
    private int $tariffNumber;

    private function __construct(int $tariffNumber)
    {
        $this->tariffNumber = $tariffNumber;
    }

    public static function fromInt(int $tariffNumber): self
    {
        return new static($tariffNumber);
    }

    public function asInt(): int
    {
        return $this->tariffNumber;
    }
}
