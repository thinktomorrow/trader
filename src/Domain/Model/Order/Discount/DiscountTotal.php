<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Discount;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\Price;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Common\Price\PriceValue;

final class DiscountTotal implements Price
{
    use PriceValue;

    private static TaxRate $discountTaxRate;

    /**
     * Discount amounts are always including tax. For the purpose of tax calculation
     * they also have the default tax rate applied to them.
     *
     * @param Money $money
     * @return static
     */
    public static function fromDefault(Money $money): static
    {
        return static::fromMoney($money, static::getDiscountTaxRate(), true);
    }

    public static function zero(): static
    {
        return new static(Cash::zero(), static::getDiscountTaxRate(), true);
    }

    public static function setDiscountTaxRate(TaxRate $taxRate)
    {
        static::$discountTaxRate = $taxRate;
    }

    public static function getDiscountTaxRate(): TaxRate
    {
        if (! isset(static::$discountTaxRate)) {
            throw new \DomainException('Please set the default tax rate for the discount. Use the DiscountTotal::setTaxRate() method.');
        }

        return static::$discountTaxRate;
    }
}
