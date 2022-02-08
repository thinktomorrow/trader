<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Domain\Events;

final class ProductsReordered
{
    public string $productGroupId;

    public function __construct(string $productGroupId)
    {
        $this->productGroupId = $productGroupId;
    }
}
