<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\UpdateProduct;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;

class UpdateProductData
{
    private string $productId;
    private array $data;

    public function __construct(string $productId, array $data)
    {
        $this->productId = $productId;
        $this->data = $data;
    }

    public function getProductId(): ProductId
    {
        return ProductId::fromString($this->productId);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
