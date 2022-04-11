<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Event;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;

final class ProductTaxaUpdated
{
    public readonly ProductId $productId;

    public function __construct(ProductId $productId)
    {
        $this->productId = $productId;
    }
}
