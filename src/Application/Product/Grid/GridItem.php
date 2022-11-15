<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\Grid;

use Money\Money;

/**
 * A grid item a product variant for the grid. If a variant is set to be show_in_grid
 * and it is available for purchase, it will be fetched as a grid item.
 */
interface GridItem
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
    public function onSale(): bool;
    public function getSaleDiscount(): string;

    public function getTitle(): string;
    public function getUrl(): string;

    public function setImages(iterable $images): void;
    public function getImages(): iterable;
}
