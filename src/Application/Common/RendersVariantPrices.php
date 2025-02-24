<?php

namespace Thinktomorrow\Trader\Application\Common;

use Money\Money;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

trait RendersVariantPrices
{
    use HasLocale;
    use RendersMoney;

    private VariantSalePrice $salePrice;
    private VariantUnitPrice $unitPrice;

    public function getSalePrice(bool $includeTax = true): string
    {
        return $this->renderMoney(
            $this->getSalePriceAsMoney($includeTax),
            $this->getLocale()
        );
    }

    public function getUnitPrice(bool $includeTax = true): string
    {
        return $this->renderMoney(
            $this->getUnitPriceAsMoney($includeTax),
            $this->getLocale()
        );
    }

    public function onSale(): bool
    {
        return $this->salePrice->getMoney()->lessThan($this->unitPrice->getMoney());
    }

    public function getSaleDiscount(): string
    {
        return $this->renderMoney(
            $this->getUnitPriceAsMoney()->subtract($this->getSalePriceAsMoney()),
            $this->getLocale()
        );
    }

    public function getUnitPriceAsMoney(bool $includeTax = true): Money
    {
        return $includeTax ? $this->unitPrice->getIncludingVat() : $this->unitPrice->getExcludingVat();
    }

    public function getSalePriceAsMoney(bool $includeTax = true): Money
    {
        return $includeTax ? $this->salePrice->getIncludingVat() : $this->salePrice->getExcludingVat();
    }

    public function getUnitPriceAsPrice(): VariantUnitPrice
    {
        return $this->unitPrice;
    }

    public function getSalePriceAsPrice(): VariantSalePrice
    {
        return $this->salePrice;
    }

    public function getTaxRateAsString(): string
    {
        return $this->salePrice->getVatPercentage()->get();
    }
}
