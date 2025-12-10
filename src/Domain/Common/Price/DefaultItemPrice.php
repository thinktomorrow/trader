<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;


/**
 * Value object representing a calculated price where the canonical state is:
 *   - excluding VAT amount
 *   - VAT percentage
 *
 * Domain logic:
 * - The canonical state is always excluding VAT.
 * - Including VAT and VAT total are always derived from the canonical state
 * - In case the price is constructed from an including VAT amount, that original
 *   amount is stored to avoid rounding drift when retrieving including VAT again.
 * - Multiplication should be done on the excluding VAT amount to avoid rounding drift.
 * - Discount should be applied to the entire line total, not per unit.
 * - ItemPrice should handle VAT correctness.
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

    public function multiply(int $quantity): static
    {
        return new static(
            $this->excludingVat->multiply($quantity),
            $this->vatPercentage
        );
    }

    public function applyDiscount(ItemDiscount $discount): static
    {
        $newExcluding = $this->excludingVat->subtract($discount->getExcludingVat());

        if ($newExcluding->isNegative()) {
            throw new PriceCannotBeNegative(
                'Applying the discount would result in a negative excluding VAT amount: ' .
                $newExcluding->getAmount()
            );
        }

        return new static($newExcluding, $this->vatPercentage);
    }

    public function changeVatPercentage(VatPercentage $vatPercentage): static
    {
        return new static($this->excludingVat, $vatPercentage);
    }
}
