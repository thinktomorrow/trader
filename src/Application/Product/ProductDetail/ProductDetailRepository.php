<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\ProductDetail;

use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

interface ProductDetailRepository
{
    public function findProductDetail(VariantId $variantId, bool $allowOffline = false): ProductDetail;
}
