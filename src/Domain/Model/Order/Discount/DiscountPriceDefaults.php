<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Discount;

use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

class DiscountPriceDefaults
{
    private static ?VatPercentage $discountTaxRate;
    private static ?bool $discountIncludeTax;

    public static function setDiscountTaxRate(VatPercentage $taxRate)
    {
        static::$discountTaxRate = $taxRate;
    }

    public static function getDiscountTaxRate(): VatPercentage
    {
        if (! isset(static::$discountTaxRate)) {
            throw new \DomainException('Please set the default tax rate for the discount. Use the DiscountTotal::setDiscountTaxRate() method.');
        }

        return static::$discountTaxRate;
    }

    public static function setDiscountIncludeTax(bool $includeTax)
    {
        static::$discountIncludeTax = $includeTax;
    }

    public static function getDiscountIncludeTax(): bool
    {
        if (! isset(static::$discountIncludeTax)) {
            throw new \DomainException('Please set the default includeTax for the discount. Use the DiscountTotal::setDiscountIncludeTax() method.');
        }

        return static::$discountIncludeTax;
    }

    // For running the test suites
    public static function clear(): void
    {
        static::$discountIncludeTax = null;
        static::$discountTaxRate = null;
    }
}
