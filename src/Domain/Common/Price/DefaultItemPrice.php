<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

/**
 * @inheritdoc
 */
class DefaultItemPrice implements ItemPrice
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
            throw new PriceCannotBeNegative(
                'Excluding VAT money amount cannot be negative: ' . $excludingVat->getAmount() . ' is given.'
            );
        }

        $this->excludingVat = $excludingVat;
        $this->vatPercentage = $vatPercentage;
    }

    public static function fromExcludingVat(Money $amount, VatPercentage $vatPercentage): static
    {
        return new static($amount, $vatPercentage);
    }

    /**
     * Factory based on a single amount and a VAT flag.
     * If $includesVat is true, we derive the excluding VAT amount.
     * If false, we take the amount as excluding VAT.
     */
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

    public function subtract(ItemPrice $price): static
    {
        if (!$this->vatPercentage->equals($price->getVatPercentage())) {
            throw new \InvalidArgumentException(
                'Cannot subtract ItemPrice with different VAT percentage (' .
                $price->getVatPercentage()->get() . '% given, ' .
                $this->vatPercentage->get() . '% expected).'
            );
        }

        $newExcluding = $this->excludingVat->subtract($price->getExcludingVat());

        if ($newExcluding->isNegative()) {
            throw new PriceCannotBeNegative(
                'Subtracting the price would result in a negative excluding VAT amount: ' .
                $newExcluding->getAmount()
            );
        }

        $self = new static($newExcluding, $this->vatPercentage);

        if ($this->includingVatOriginal) {
            $newIncluding = $this->includingVatOriginal->subtract($price->getIncludingVat());
            $self->includingVatOriginal = $newIncluding;
        }

        return $self;
    }

    public function multiply(int $quantity): static
    {
        $self = new static(
            $this->excludingVat->multiply($quantity),
            $this->vatPercentage
        );

        if ($this->includingVatOriginal) {
            $self->includingVatOriginal = $this->includingVatOriginal->multiply($quantity);
        }

        return $self;
    }

    public function applyDiscount(DiscountPrice $discount): static
    {
        $newExcluding = $this->excludingVat->subtract($discount->getExcludingVat());

        if ($newExcluding->isNegative()) {
            throw new PriceCannotBeNegative(
                'Applying the discount would result in a negative excluding VAT amount: ' .
                $newExcluding->getAmount()
            );
        }

        $self = new static($newExcluding, $this->vatPercentage);

        if ($this->includingVatOriginal) {

            $discountIncludingVat = Cash::from($discount->getExcludingVat())->addPercentage($this->vatPercentage->toPercentage());

            $newIncluding = $this->includingVatOriginal->subtract($discountIncludingVat);
            $self->includingVatOriginal = $newIncluding;
        }

        return $self;
    }

    public function changeVatPercentage(VatPercentage $vatPercentage): static
    {
        return new static($this->excludingVat, $vatPercentage);
    }

    public function includingIsAuthoritative(): bool
    {
        return $this->includingVatOriginal !== null;
    }
}
