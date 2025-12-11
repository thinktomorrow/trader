<?php

namespace Thinktomorrow\Trader\Domain\Common\Price;

trait WithPriceInputMode
{
    protected bool $priceEnteredIncludingVat = false;

    protected function priceEnteredIncludesVat(): bool
    {
        return $this->priceEnteredIncludingVat;
    }

    protected function setPriceEnteredIncludingVat(bool $priceEnteredIncludingVat): void
    {
        $this->priceEnteredIncludingVat = $priceEnteredIncludingVat;
    }
}
