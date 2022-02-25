<?php

namespace Thinktomorrow\Trader\Application\Product;

use Thinktomorrow\Trader\Application\Common\RendersMoney;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

trait RendersPrices
{
    use RendersMoney;

    private VariantSalePrice $salePrice;
    private VariantUnitPrice $unitPrice;

    public function getSalePrice(bool $includeTax = true): string
    {
        return $this->renderMoney(
            $includeTax ? $this->salePrice->getIncludingVat() : $this->salePrice->getExcludingVat(),
            $this->locale
        );
    }

    public function getUnitPrice(bool $includeTax = true): string
    {
        return $this->renderMoney(
            $includeTax ? $this->unitPrice->getIncludingVat() : $this->unitPrice->getExcludingVat(),
            $this->locale
        );
    }

    public function isOnSale(): bool
    {
        return $this->salePrice->getMoney()->lessThan($this->unitPrice->getMoney());
    }
}
