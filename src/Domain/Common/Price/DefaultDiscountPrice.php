<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

class DefaultDiscountPrice implements DiscountPrice
{
    private Money $excludingVat;

    private function __construct(Money $excludingVat)
    {
        if ($excludingVat->isNegative()) {
            throw new PriceCannotBeNegative('Excluding VAT money amount cannot be negative: ' . $excludingVat->getAmount() . ' is given.');
        }

        $this->excludingVat = $excludingVat;
    }

    public static function fromExcludingVat(Money $amount): static
    {
        return new static($amount);
    }

    public static function fromIncludingVat(Money $includingVat, VatPercentage $vatPercentage): static
    {
        $excludingVat = Cash::from($includingVat)
            ->subtractPercentage($vatPercentage->get());

        return new self($excludingVat);
    }

    public static function zero(): static
    {
        return new static(Cash::zero());
    }

    public function getExcludingVat(): Money
    {
        return $this->excludingVat;
    }

    public function getIncludingVat(VatPercentage $vatPercentage): Money
    {
        return Cash::from($this->excludingVat)
            ->addPercentage($vatPercentage->toPercentage());
    }

    public function add(DiscountPrice $discountPrice): static
    {
        return new static($this->excludingVat->add($discountPrice->getExcludingVat()));
    }

    public function multiply(int $quantity): static
    {
        return new static($this->excludingVat->multiply($quantity));
    }
}
