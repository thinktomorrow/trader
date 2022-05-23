<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;

class DeleteProduct
{
    private string $productId;

    public function __construct(string $productId)
    {
        $this->productId = $productId;
    }

    public function getProductId(): ProductId
    {
        return ProductId::fromString($this->productId);
    }
}
