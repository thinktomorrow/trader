<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Domain\Events;

final class ProductQueuedForDeletion
{
    public string $productId;

    public function __construct(string $productId)
    {
        $this->productId = $productId;
    }
}