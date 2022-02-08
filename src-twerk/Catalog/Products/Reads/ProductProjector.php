<?php

namespace Thinktomorrow\Trader\Catalog\Products\Reads;

use Thinktomorrow\Trader\Catalog\Products\Domain\Events\ProductCreated;

class ProductProjector
{
    final public function __construct()
    {
    }

    // based on events ...
    // onProductDetailsChanged()
    // Adjusters on projection...
    public function onProductTextChanged(ProductCreated $event)
    {
        $this->productReadProjection->replaceOrAdd($event->productId);
    }
}
