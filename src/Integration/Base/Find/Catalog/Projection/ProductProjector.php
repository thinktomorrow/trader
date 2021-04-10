<?php

namespace Thinktomorrow\Trader\Integration\Base\Find\Catalog\Reads;

use Thinktomorrow\Trader\Find\Catalog\Domain\Events\ProductTextChanged;

class ProductProjector
{
    final public function __construct()
    {

    }

    // based on events ...
    // onProductDetailsChanged()
    // Adjusters on projection...
    public function onProductTextChanged(ProductTextChanged $event)
    {
        $this->productReadProjection->replaceOrAdd($event->productId);
    }
}
