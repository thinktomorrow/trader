<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\Grid;

use Money\Money;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Application\Product\Taxa\VariantTaxonItem;

/**
 * A grid item a product variant for the grid. If a variant is set to be show_in_grid,
 * and it is available for purchase, it will be fetched as a grid item.
 */
interface GridItem
{
    public static function fromMappedData(array $state, array $taxa): static;

    public function getVariantId(): string;

    public function getProductId(): string;

    /**
     * All related VariantTaxon and ProductTaxon objects.
     * @return array<VariantTaxonItem|ProductTaxonItem>
     */
    public function getTaxa(): array;

    public function getMainCategory(): ?ProductTaxonItem;

    public function getGridCategories(): array;

    public function getGridProductProperties(): array;

    public function getGridVariantProperties(): array;

    public function getGridCollections(): array;

    public function getGridTags(): array;

    public function isAvailable(): bool;

    public function getUnitPrice(): string;

    public function getSalePrice(): string;

    public function getUnitPriceAsMoney(): Money;

    public function getSalePriceAsMoney(): Money;

    public function getTaxRateAsString(): string;

    public function onSale(): bool;

    public function getSaleDiscount(): string;

    public function getTitle(): string;

    public function getUrl(): string;

    public function setImages(iterable $images): void;

    public function getImages(): iterable;
}
