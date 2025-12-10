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
        } else {
            $excludingVat = $amount;
        }

        return new static($excludingVat, $vatPercentage);
    }

    public function getIncludingVat(): Money
    {
        return Cash::from($this->excludingVat)->addPercentage($this->vatPercentage->toPercentage());
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
        return new static(
            $this->excludingVat->add($otherItemDiscount->getExcludingVat()),
            $this->vatPercentage
        );
    }
}
