<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

class DefaultItemDiscount implements ItemDiscount
{
    private Money $excludingVat;
    private VatPercentage $vatPercentage;

    /**
     * Stores the originally provided VAT-inclusive amount (when given),
     * so we can return it without re-computing and causing rounding drift.
     */
    private ?Money $includingVatOriginal = null;

    private function __construct(Money $excludingVat, VatPercentage $vatPercentage)
    {
        if ($excludingVat->isNegative()) {
            throw new PriceCannotBeNegative('Excluding VAT money amount cannot be negative: ' . $excludingVat->getAmount() . ' is given.');
        }

        $this->excludingVat = $excludingVat;
        $this->vatPercentage = $vatPercentage;
    }

    public static function fromExcludingVat(Money $amount, VatPercentage $vatPercentage): static
    {
        return new static($amount, $vatPercentage);
    }

    public static function fromMoney(Money $amount, VatPercentage $vatPercentage, bool $includesVat): static
    {
        if ($includesVat) {
            $excludingVat = Cash::from($amount)->subtractTaxPercentage($vatPercentage->toPercentage());

            $self = new static($excludingVat, $vatPercentage);
            $self->includingVatOriginal = $amount;

            return $self;
        }

        return new static($amount, $vatPercentage);
    }

    public static function fromScalars(int|string $amount, string $vatPercentage, bool $includesVat): static
    {
        return static::fromMoney(
            Cash::make($amount),
            VatPercentage::fromString($vatPercentage),
            $includesVat
        );
    }

    public function getIncludingVat(): Money
    {
        return $this->includingVatOriginal ?? Cash::from($this->excludingVat)->addPercentage($this->vatPercentage->toPercentage());
    }

    public function getExcludingVat(): Money
    {
        return $this->excludingVat;
    }

    public function getVatTotal(): Money
    {
        return $this->getIncludingVat()->subtract($this->excludingVat);
    }

    public function getVatPercentage(): VatPercentage
    {
        return $this->vatPercentage;
    }

    public function add(ItemDiscount $otherItemDiscount): static
    {
        $self = new static(
            $this->excludingVat->add($otherItemDiscount->getExcludingVat()),
            $this->vatPercentage
        );

        if ($this->includingVatOriginal) {
            $self->includingVatOriginal = $this->includingVatOriginal->add($otherItemDiscount->getIncludingVat());
        }

        return $self;
    }

    public function hasOriginalIncludingVat(): bool
    {
        return $this->includingVatOriginal !== null;
    }
}
