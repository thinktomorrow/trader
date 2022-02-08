<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Domain\Events;

class ProductGroupCreated
{
    public string $productGroupId;

    public function __construct(string $productGroupId)
    {
        $this->productGroupId = $productGroupId;
    }
}
