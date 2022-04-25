<?php

namespace Thinktomorrow\Trader\Application\Common;

use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

trait RendersPrices
{
    use HasLocale;
    use RendersMoney;

    private VariantSalePrice $salePrice;
    private VariantUnitPrice $unitPrice;

    public function getSalePrice(bool $includeTax = true): string
    {
        return $this->renderMoney(
            $includeTax ? $this->salePrice->getIncludingVat() : $this->salePrice->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function getUnitPrice(bool $includeTax = true): string
    {
        return $this->renderMoney(
            $includeTax ? $this->unitPrice->getIncludingVat() : $this->unitPrice->getExcludingVat(),
            $this->getLocale()
        );
    }

    public function isOnSale(): bool
    {
        return $this->salePrice->getMoney()->lessThan($this->unitPrice->getMoney());
    }
}
