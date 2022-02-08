<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

interface ProductRepository
{
    public function save(Product $product): void;

    public function find(ProductId $productId): Product;

    public function delete(ProductId $productId): void;

    public function nextReference(): ProductId;
}
