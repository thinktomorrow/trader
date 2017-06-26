<?php

use ThinkTomorrow\Trader\Order\Domain\Adjusters\Adjustable;
use ThinkTomorrow\Trader\Order\Domain\Adjusters\Adjuster;
use ThinkTomorrow\Trader\Order\Domain\Adjusters\Adjustment;

class ItemSalespriceAdjuster implements Adjuster
{

    public function adjust(Adjustable $adjustable): Adjustment
    {
        $amount = $adjustable->adjustableAmount();

        return new Adjustment();
    }
}