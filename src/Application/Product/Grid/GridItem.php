<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\Grid;

use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Application\Product\Taxa\VariantTaxonItem;
use Thinktomorrow\Trader\Domain\Common\Price\ItemPrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantSalePrice;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;

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

    public function getTitle(): string;

    public function getUrl(): string;

    public function setImages(iterable $images): void;

    public function getImages(): iterable;

    public function getData(?string $key = null, $default = null): mixed;
}
