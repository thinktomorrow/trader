<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

class DeleteVariant
{
    private string $productId;
    private string $variantId;

    public function __construct(string $productId, string $variantId)
    {
        $this->productId = $productId;
        $this->variantId = $variantId;
    }

    public function getProductId(): ProductId
    {
        return ProductId::fromString($this->productId);
    }

    public function getVariantId(): VariantId
    {
        return VariantId::fromString($this->variantId);
    }
}
