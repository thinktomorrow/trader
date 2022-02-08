<?php

namespace Base\Find\Catalog\Projection;

use Find\Catalog\Domain\Events\ProductTextChanged;

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
