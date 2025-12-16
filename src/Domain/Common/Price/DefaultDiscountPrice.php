<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;

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

    public static function zero(): static
    {
        return new static(Cash::zero());
    }

    public function getExcludingVat(): Money
    {
        return $this->excludingVat;
    }

    public function add(DiscountPrice $discountPrice): static
    {
        return new static($this->excludingVat->add($discountPrice->getExcludingVat()));
    }
}
