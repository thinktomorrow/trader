<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Price\Exceptions\PriceCannotBeNegative;

/**
 * @inheritdoc
 */
class DefaultServicePrice implements ServicePrice
{
    private Money $excludingVat;

    private function __construct(Money $excludingVat)
    {
        if ($excludingVat->isNegative()) {
            throw new PriceCannotBeNegative(
                'Excluding VAT money amount cannot be negative: ' . $excludingVat->getAmount() . ' is given.'
            );
        }

        $this->excludingVat = $excludingVat;
    }

    public static function fromExcludingVat(Money $excludingVat): static
    {
        return new static($excludingVat);
    }

    public function getExcludingVat(): Money
    {
        return $this->excludingVat;
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

        return new static($newExcluding);
    }
}
