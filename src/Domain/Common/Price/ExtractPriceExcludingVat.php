<?php

namespace Thinktomorrow\Trader\Domain\Common\Price;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

/**
 * Backed compatibility function to extract price excluding vat from state array.
 *
 * Services like shipping, payment, discount are always priced excluding vat.
 * They have no vat on their own. But in former versions of trader,
 * includes_vat flag was used. This check is kept for backward
 * compatibility, but has no effect on current calculation.
 */
class ExtractPriceExcludingVat
{
    public static function extract(array $state, string $priceKey): Money
    {
        if (isset($state['includes_vat'], $state['tax_rate']) && $state['includes_vat'] && $state['tax_rate']) {
            return Cash::from(Cash::make($state[$priceKey]))->subtractTaxPercentage(VatPercentage::fromString($state['tax_rate'])->toPercentage());
        }

        return Cash::make($state[$priceKey]);
    }
}
