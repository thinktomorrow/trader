<?php

namespace Thinktomorrow\Trader\Sales\Domain\Types;

use Thinktomorrow\Trader\Order\Domain\Purchasable;
use Thinktomorrow\Trader\Sales\Domain\Sale;

class PercentageOffSale extends BaseSale implements Sale
{
    public function apply(Purchasable $purchasable)
    {

    }
}