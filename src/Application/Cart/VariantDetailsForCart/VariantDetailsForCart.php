<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\VariantDetailsForCart;

use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;

class VariantDetailsForCart
{
    private VariantSalePrice $variantSalePrice;

    public function __construct(VariantSalePrice $variantSalePrice)
    {
        $this->variantSalePrice = $variantSalePrice;
    }

    public function getSalePrice(): VariantSalePrice
    {
        return $this->variantSalePrice;
    }
}
