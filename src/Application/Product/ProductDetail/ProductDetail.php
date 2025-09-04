<?php

namespace Thinktomorrow\Trader\Application\Product\ProductDetail;

use Money\Money;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Application\Product\Taxa\VariantTaxonItem;
use Thinktomorrow\Trader\Application\Stock\Read\Stockable;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

interface ProductDetail extends Stockable
{
    /**
     * @param array $state
     * @param array<VariantTaxonItem|ProductTaxonItem> $taxa
     * @return static
     */
    public static function fromMappedData(array $state, array $taxa): static;

    public function getVariantId(): string;

    public function getProductId(): string;

    /**
     * All related VariantTaxon and ProductTaxon objects.
     * @return array<VariantTaxonItem|ProductTaxonItem>
     */
    public function getTaxa(): array;

    public function getMainCategory(): ?ProductTaxonItem;

    public function getCategories(): array;

    public function getGoogleCategories(): array;

    public function getProductProperties(): array;

    /**
     * All available variant properties of the product
     * @return array<ProductTaxonItem>
     */
    public function getProductVariantProperties(): array;

    /**
     * @return array<VariantTaxonItem>
     */
    public function getVariantProperties(): array;

    public function getCollections(): array;

    public function getTags(): array;

    public function isAvailable(): bool;

    public function getUnitPrice(): string;

    public function getSalePrice(): string;

    public function getUnitPriceAsMoney(): Money;

    public function getSalePriceAsMoney(): Money;

    public function getUnitPriceAsPrice(): VariantUnitPrice;

    public function getSalePriceAsPrice(): VariantSalePrice;

    public function getTaxRateAsString(): string;

    public function onSale(): bool;

    public function getSaleDiscount(): string;

    public function getTitle(?string $locale = null): string;

    public function getIntro(?string $locale = null): string;

    public function getContent(?string $locale = null): string;

    public function getUrl(?string $locale = null): string;

    public function getSku(): string;

    public function getEan(): ?string;

    public function setImages(iterable $images): void;

    public function getImages(): iterable;

    /**
     * This is used by the query builder to determine which values to return. This
     * is only for custom data to return e.g. on the product table that is not
     * included by default in the ProductDetail query result. Period. End.
     *
     * @return array
     */
    public static function stateSelect(): array;
}
