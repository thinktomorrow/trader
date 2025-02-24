<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Vat;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Cash\PreciseMoney;

class VatTotal
{
    // Amount of decimals we will use to calculate the vat. This will ensure a better accuracy.
    const VAT_CALCULATION_PRECISION = 4;

    private VatPercentage $vatPercentage;

    /***
     * Nett amount where vat should be calculated upon.
     * The VatApplicableTotal has a more precise amount than the regular Money object
     */
    private VatApplicableTotal $vatApplicableTotal;

    public function __construct(VatPercentage $vatPercentage, VatApplicableTotal $vatApplicableTotal)
    {
        $this->vatPercentage = $vatPercentage;
        $this->vatApplicableTotal = $vatApplicableTotal;
    }

    public static function zero(VatPercentage $vatPercentage): static
    {
        return new static($vatPercentage, VatApplicableTotal::zero(static::VAT_CALCULATION_PRECISION));
    }

    public function add(VatApplicableTotal $vatApplicableTotal): static
    {
        return new static($this->vatPercentage, $this->vatApplicableTotal->add($vatApplicableTotal));
    }

    public function subtract(VatApplicableTotal $vatApplicableTotal): static
    {
        if ($vatApplicableTotal->getPreciseMoney()->greaterThanOrEqual($this->vatApplicableTotal->getPreciseMoney())) {
            return new static($this->vatPercentage, VatApplicableTotal::zero(static::VAT_CALCULATION_PRECISION, $this->vatApplicableTotal->getMoney()->getCurrency()->getCode()));
        }

        return new static($this->vatPercentage, $this->vatApplicableTotal->subtract($vatApplicableTotal));
    }

    public function getVatPercentage(): VatPercentage
    {
        return $this->vatPercentage;
    }

    public function getVatApplicableTotal(): VatApplicableTotal
    {
        return $this->vatApplicableTotal;
    }

    public function getVatTotal(): Money
    {
        $grossTotal = Cash::from($this->vatApplicableTotal->getMoney())->addPercentage($this->vatPercentage->toPercentage());

        return $grossTotal->subtract($this->vatApplicableTotal->getMoney());
    }

    public function getPreciseVatTotal(): PreciseMoney
    {
        $grossTotalAmount = $this->vatApplicableTotal->getPreciseMoney()->getAmount() * ((float)$this->vatPercentage->toPercentage()->toDecimal() + 1);
        $grossTotal = VatApplicableTotal::fromFloat($grossTotalAmount, static::VAT_CALCULATION_PRECISION);

        return $grossTotal->subtract($this->vatApplicableTotal);
    }
}
