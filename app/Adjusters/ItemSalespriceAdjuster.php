<?php

use Thinktomorrow\Trader\Orders\Domain\Adjusters\Adjustable;
use Thinktomorrow\Trader\Orders\Domain\Adjusters\Adjuster;
use Thinktomorrow\Trader\Orders\Domain\Adjusters\Adjustment;

class ItemSalespriceAdjuster implements Adjuster
{
    public function adjust(Adjustable $adjustable): Adjustment
    {
        $amount = $adjustable->adjustableAmount();

        return new Adjustment();
    }
}
