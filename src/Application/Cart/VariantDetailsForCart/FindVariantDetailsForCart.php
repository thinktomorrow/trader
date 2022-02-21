<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\VariantDetailsForCart;

use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

interface FindVariantDetailsForCart
{
    public function findVariantDetailsForCart(VariantId $variantId): VariantDetailsForCart;
}
