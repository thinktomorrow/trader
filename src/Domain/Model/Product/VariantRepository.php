<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product;

use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

interface VariantRepository
{
    public function save(Variant $variant): void;

    /**
     * @return array with two entries: [0 => state data, 1 => child entities].
     * Used internally by product repository to fetch all variants.
     */
    public function getStatesByProduct(ProductId $productId): array;

    public function delete(VariantId $variantId): void;

    public function nextReference(): VariantId;
}
