<?php

namespace Thinktomorrow\Trader\Application\Product\ProductDetail;

use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Application\Product\Taxa\VariantTaxonItem;
use Thinktomorrow\Trader\Application\Stock\Read\Stockable;
use Thinktomorrow\Trader\Domain\Common\Price\ItemPrice;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

interface ProductDetail extends Stockable
{
    /**
     * @param  array<VariantTaxonItem|ProductTaxonItem>  $taxa
     */
    public static function fromMappedData(array $state, array $taxa, array $variantKeys, array $personalisations): static;

    public function getVariantId(): string;

    public function getProductId(): string;

    /**
     * All related VariantTaxon and ProductTaxon objects.
     *
     * @return array<VariantTaxonItem|ProductTaxonItem>
     */
    public function getTaxa(): array;

    public function getMainCategory(): ?ProductTaxonItem;

    public function getCategories(): array;

    public function getGoogleCategories(): array;

    public function getProductProperties(): array;

    /**
     * All available variant properties of the product
     *
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

    public function getUnitPrice(): VariantUnitPrice;

    public function getSalePrice(): VariantSalePrice;

    public function getSaleDiscountPrice(): ItemPrice;

    public function getFormattedUnitPriceExcl(): string;

    public function getFormattedUnitPriceIncl(): string;

    public function getFormattedSalePriceExcl(): string;

    public function getFormattedSalePriceIncl(): string;

    public function getFormattedVatRate(): string;

    public function onSale(): bool;

    public function getFormattedSaleDiscountPriceExcl(): string;

    public function getFormattedSaleDiscountPriceIncl(): string;

    public function getSaleDiscountPercentage(): int;

    public function getTitle(?string $locale = null): string;

    public function getIntro(?string $locale = null): string;

    public function getContent(?string $locale = null): string;

    public function getSku(): string;

    public function getEan(): ?string;

    public function setImages(iterable $images): void;

    public function getImages(): iterable;

    /**
     * This is used by the query builder to determine which values to return. This
     * is only for custom data to return e.g. on the product table that is not
     * included by default in the ProductDetail query result. Period. End.
     */
    public static function stateSelect(): array;

    public function getKey(?string $locale = null): ?string;

    public function getUrl(?string $locale = null): string;

    /** @return Personalisation[] */
    public function getPersonalisations(): array;

    public function getData(?string $key = null, $default = null): mixed;

    public function getProductData(?string $key = null, $default = null): mixed;
}
