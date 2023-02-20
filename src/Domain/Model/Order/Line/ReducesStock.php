<?php

namespace Thinktomorrow\Trader\Domain\Model\Order\Line;

trait ReducesStock
{
    private bool $reducedFromStock = false;

    public function reduceFromStock(): void
    {
        $this->reducedFromStock = true;
    }

    public function reducedFromStock(): bool
    {
        return $this->reducedFromStock;
    }
}
