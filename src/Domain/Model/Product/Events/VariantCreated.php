<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Events;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

final class VariantCreated
{
    public readonly ProductId $productId;
    public readonly VariantId $variantId;

    public function __construct(ProductId $productId, VariantId $variantId)
    {
        $this->productId = $productId;
        $this->variantId = $variantId;
    }
}
