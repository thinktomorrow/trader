<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\VariantForCart;

use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

interface VariantForCartRepository
{
    /**
     * @deprecated use ProductDetailRepository->findProductDetail() instead
     */
    public function findVariantForCart(VariantId $variantId): VariantForCart;

    public function findAllVariantsForCart(array $variantIds): array;
}
