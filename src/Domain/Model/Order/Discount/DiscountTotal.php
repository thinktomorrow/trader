<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Discount;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\Price;
use Thinktomorrow\Trader\Domain\Common\Price\PriceValue;

final class DiscountTotal implements Price
{
    use PriceValue;

    /**
     * Discount amounts are always including tax. For the purpose of tax calculation
     * they also have the default tax rate applied to them.
     *
     * @param Money $money
     * @return static
     */
    public static function fromDefault(Money $money): static
    {
        return static::fromMoney($money, DiscountPriceDefaults::getDiscountTaxRate(), DiscountPriceDefaults::getDiscountIncludeTax());
    }

    public static function zero(): static
    {
        return static::fromDefault(Cash::zero());
    }
}
