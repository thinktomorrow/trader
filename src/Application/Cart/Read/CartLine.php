<?php

namespace Thinktomorrow\Trader\Application\Cart\Read;

use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;

interface CartLine
{
    public static function fromMappedData(array $state, array $orderState, iterable $discounts): static;

    public function getLineId(): string;
    public function getProductId(): string;
    public function getVariantId(): string;

    public function getUnitPrice(): string;
    public function getLinePrice(): string;
    public function getTotalPrice(): string;
    public function getSubtotalPrice(): string;
    public function getTaxPrice(): string;
    public function getDiscountPrice(): string;
    public function includeTax(bool $includeTax = true): void;

    public function getQuantity(): int;

    public function getTitle(): string;
    public function getDescription(): ?string;
    public function setImages(iterable $images): void;
    public function getImages(): iterable;
    public function getUrl(): string;

    /** @return CartDiscount[] */
    public function getDiscounts(): iterable;
}
