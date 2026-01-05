<?php

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\OrderRead;

trait WithFormattedServiceTotals
{
    public function getFormattedCostPriceExcl(): string
    {
        return $this->renderMoney(
            $this->getCostPriceExcl(),
            $this->getLocale()
        );
    }

    public function getFormattedDiscountPriceExcl(): string
    {
        return $this->renderMoney(
            $this->getDiscountPriceExcl(),
            $this->getLocale()
        );
    }

    public function getFormattedTotalPriceExcl(): string
    {
        return $this->renderMoney(
            $this->getTotalPriceExcl(),
            $this->getLocale()
        );
    }
}
