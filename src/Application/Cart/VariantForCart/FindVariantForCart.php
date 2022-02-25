<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\VariantForCart;

use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

interface FindVariantForCart
{
    public function findVariantForCart(VariantId $variantId): VariantForCart;
}
