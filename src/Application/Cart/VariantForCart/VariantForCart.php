<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\VariantForCart;

use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;

class VariantForCart
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

    public static function fromMappedData(array $state): static
    {
        return new static(
            VariantSalePrice::fromScalars($state['sale_price'], 'EUR', $state['tax_rate'], $state['includes_vat'])
        );
    }
}
