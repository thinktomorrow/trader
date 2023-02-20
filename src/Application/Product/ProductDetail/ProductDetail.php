<?php

namespace Thinktomorrow\Trader\Application\Product\ProductDetail;

use Money\Money;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

interface ProductDetail
{
    public static function fromMappedData(array $state): static;

    public function getVariantId(): string;
    public function getProductId(): string;
    public function getTaxonIds(): array;
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
