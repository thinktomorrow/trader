<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\GetProductOptions;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;

interface ProductOptionsRepository
{
    public function getProductOptions(ProductId $productId): ProductOptions;
}
