<?php

namespace Thinktomorrow\Trader\Application\Common;

use Thinktomorrow\Trader\Domain\Common\Price\ItemPrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

trait RendersVariantPrices
{
    use HasLocale;
    use RendersMoney;

    protected VariantSalePrice $salePrice;

    protected VariantUnitPrice $unitPrice;

    public function getUnitPrice(): VariantUnitPrice
    {
        return $this->unitPrice;
    }

    public function getSalePrice(): VariantSalePrice
    {
        return $this->salePrice;
    }

    public function getSaleDiscountPrice(): ItemPrice
    {
        return $this->unitPrice->subtract($this->salePrice);
    }

    public function getFormattedUnitPriceExcl(): string
    {
        return $this->renderMoney(
            $this->getUnitPrice()->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getFormattedUnitPriceIncl(): string
    {
        return $this->renderMoney(
            $this->getUnitPrice()->getIncludingVat(),
            $this->getLocale()
        );
    }

    public function getFormattedSalePriceExcl(): string
    {
        return $this->renderMoney(
            $this->getSalePrice()->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getFormattedSalePriceIncl(): string
    {
        return $this->renderMoney(
            $this->getSalePrice()->getIncludingVat(),
            $this->getLocale()
        );
    }

    public function getFormattedVatRate(): string
    {
        return $this->salePrice->getVatPercentage()->get();
    }

    public function onSale(): bool
    {
        if (! $this->isPurchasableOnline()) {
            return false;
        }

        return $this->salePrice->getExcludingVat()->lessThan($this->unitPrice->getExcludingVat());
    }

    public function getFormattedSaleDiscountPriceExcl(): string
    {
        return $this->renderMoney(
            $this->getSaleDiscountPrice()->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getFormattedSaleDiscountPriceIncl(): string
    {
        return $this->renderMoney(
            $this->getSaleDiscountPrice()->getIncludingVat(),
            $this->getLocale()
        );
    }

    public function getSaleDiscountPercentage(): int
    {
        $unitPrice = $this->getUnitPrice()->getExcludingVat()->getAmount();
        $salePrice = $this->getSalePrice()->getExcludingVat()->getAmount();

        if ($unitPrice == 0) {
            return 0;
        }

        $percentage = (($unitPrice - $salePrice) / $unitPrice) * 100;

        return round($percentage);
    }
}
