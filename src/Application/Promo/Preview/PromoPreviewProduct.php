<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Preview;

use Money\Money;
use Thinktomorrow\Trader\Application\Cart\Read\CartLine;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;

final readonly class PromoPreviewProduct
{
    /** @param list<string> $taxonIds */
    public function __construct(
        public ?string $productId,
        public string $variantId,
        public array $taxonIds,
        public Money $unitPriceExcl,
        public Money $salePriceExcl,
        /** Amount added to the cart; zero when this product is already in it. */
        public int $quantityToAdd,
    ) {}

    public static function fromProductDetail(ProductDetail $product, int $quantityToAdd): self
    {
        return new self(
            $product->getProductId(),
            $product->getVariantId(),
            array_map(fn ($taxon): string => $taxon->getTaxonId(), $product->getTaxa()),
            $product->getUnitPrice()->getExcludingVat(),
            $product->getSalePrice()->getExcludingVat(),
            $quantityToAdd,
        );
    }

    public static function fromCartLine(CartLine $line): self
    {
        $unitPrice = $line->getUnitPriceExcl();
        $salePrice = $line->getData('sale_price_excl');

        return new self(
            $line->getData('product_id'),
            $line->getPurchasableReference()->getId(),
            array_values(array_filter(array_column($line->getData('taxa', []), 'taxon_id'))),
            $unitPrice,
            $salePrice !== null ? Money::EUR($salePrice) : $unitPrice,
            0,
        );
    }

    public function isOnSale(): bool
    {
        return $this->salePriceExcl->lessThan($this->unitPriceExcl);
    }
}
