<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Vat;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;

class VatTotal
{
    private VatPercentage $vatPercentage;

    private Money $total;

    public function __construct(VatPercentage $vatPercentage, Money $total)
    {
        $this->vatPercentage = $vatPercentage;
        $this->total = $total;
    }

    public static function make(VatPercentage $vatPercentage, Money $total): static
    {
        return new static($vatPercentage, $total);
    }

    public static function zero(VatPercentage $vatPercentage): static
    {
        return new static($vatPercentage, Cash::zero());
    }

    public function add(Money $addedTotal): static
    {
        return new static($this->vatPercentage, $this->total->add($addedTotal));
    }

    public function subtract(Money $subtractedTotal): static
    {
        if ($subtractedTotal->greaterThanOrEqual($this->total)) {
            return new static($this->vatPercentage, Cash::zero());
        }

        return new static($this->vatPercentage, $this->total->subtract($subtractedTotal));
    }

    public function getVatPercentage(): VatPercentage
    {
        return $this->vatPercentage;
    }

    public function getTotal(): Money
    {
        return $this->total;
    }
}
