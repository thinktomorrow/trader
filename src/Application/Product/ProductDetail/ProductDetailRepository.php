<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\ProductDetail;

use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultProductDetail;

interface ProductDetailRepository
{
    public function findProductDetail(VariantId $variantId): DefaultProductDetail;
}
