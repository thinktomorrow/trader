<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Ports;

use Money\Money;
use Thinktomorrow\Trader\Catalog\Products\Domain\Product;
use Thinktomorrow\Trader\Common\Cash\Cash;
use Thinktomorrow\Trader\Taxes\TaxRate;

final class ProductPrice
{
    public function getPriceInclusiveTax(Money $price, TaxRate $taxRate, bool $doesPriceIncludeTax): Money
    {
        if (! $doesPriceIncludeTax) {
            return Cash::from($price)->addPercentage(
                $taxRate->toPercentage()
            );
        }

        return $price;
    }

    public function getPriceExclusiveTax(Money $price, TaxRate $taxRate, bool $doesPriceIncludeTax): Money
    {
        if ($doesPriceIncludeTax) {
            return Cash::from($price)->subtractTaxPercentage(
                $taxRate->toPercentage()
            );
        }

        return $price;
    }

    public function getTotalInclusiveTax(Product $product): Money
    {
        return $this->getPriceInclusiveTax($product->getTotal(), $product->getTaxRate(), $product->doPricesIncludeTax());
    }

    public function getTotalExclusiveTax(Product $product): Money
    {
        return $this->getPriceExclusiveTax($product->getTotal(), $product->getTaxRate(), $product->doPricesIncludeTax());
    }

    public function getUnitPriceInclusiveTax(Product $product): Money
    {
        return $this->getPriceInclusiveTax($product->getUnitPrice(), $product->getTaxRate(), $product->doPricesIncludeTax());
    }

    public function getUnitPriceExclusiveTax(Product $product): Money
    {
        return $this->getPriceExclusiveTax($product->getUnitPrice(), $product->getTaxRate(), $product->doPricesIncludeTax());
    }
}
