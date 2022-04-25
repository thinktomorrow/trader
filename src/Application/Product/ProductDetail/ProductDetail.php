<?php

namespace Thinktomorrow\Trader\Application\Product\ProductDetail;

use Money\Money;

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

    public function getTitle(): string;
    public function getIntro(): string;
    public function getContent(): string;
    public function getSku(): string;
    public function getUrl(): string;

    public function setImages(iterable $images): void;
    public function getImages(): iterable;
}
