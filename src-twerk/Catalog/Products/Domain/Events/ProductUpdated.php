<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Domain\Events;

class ProductUpdated
{
    public string $productId;

    public function __construct(string $productId)
    {
        $this->productId = $productId;
    }
}
