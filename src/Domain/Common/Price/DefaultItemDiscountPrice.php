<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

class DefaultItemDiscountPrice implements ItemDiscountPrice
{
    private Money $excludingVat;

    private VatPercentage $vatPercentage;

    /**
     * Stores the originally provided VAT-inclusive discount amount (when given),
     * so we can return it without re-computing and causing rounding drift.
     *
     * When including is given, we opt to use this as source
     * of truth for calculations of item discounts.
     */
    private ?Money $includingVat = null;

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

    public static function fromIncludingVat(Money $includingVat, VatPercentage $vatPercentage): static
    {
        $excludingVat = Cash::from($includingVat)
            ->subtractPercentage($vatPercentage->get());

        $model = new self($excludingVat, $vatPercentage);

        $model->includingVat = $includingVat;

        return $model;
    }

    public static function zero(VatPercentage $vatPercentage, bool $includingVatAuthoritative = false): static
    {
        $model = new static(Cash::zero(), $vatPercentage);

        if ($includingVatAuthoritative) {
            $model->includingVat = Cash::zero();
        }

        return $model;
    }

    public function getExcludingVat(): Money
    {
        return $this->excludingVat;
    }

    public function getIncludingVat(): Money
    {
        if ($this->includingVat !== null) {
            return $this->includingVat;
        }

        return Cash::from($this->excludingVat)
            ->addPercentage($this->vatPercentage->toPercentage());
    }

    public function add(ItemDiscountPrice $discountPrice): static
    {
        if ($this->includingVat) {
            return static::fromIncludingVat(
                $this->includingVat->add($discountPrice->getIncludingVat()),
                $this->vatPercentage
            );
        }

        return static::fromExcludingVat(
            $this->excludingVat->add($discountPrice->getExcludingVat()),
            $this->vatPercentage
        );
    }

    public function multiply(int $quantity): static
    {
        if ($this->includingVat) {
            return static::fromIncludingVat(
                $this->includingVat->multiply($quantity),
                $this->vatPercentage
            );
        }

        return static::fromExcludingVat(
            $this->excludingVat->multiply($quantity),
            $this->vatPercentage
        );
    }

    public function getVatPercentage(): VatPercentage
    {
        return $this->vatPercentage;
    }

    public function isIncludingVatAuthoritative(): bool
    {
        return $this->includingVat !== null;
    }
}
