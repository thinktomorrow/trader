<?php

namespace Thinktomorrow\Trader\Application\Cart\Read;

use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;

interface CartLine
{
    public static function fromMappedData(array $state, VariantForCart $variantForCart, iterable $discounts): static;

    public function getLineId(): string;

    public function getLinePrice(): string;
    public function getTotalPrice(): string;
    public function getSubtotalPrice(): string;
    public function getTaxPrice(): string;
    public function getDiscountPrice(): string;
    public function includeTax(bool $includeTax = true): void;

    public function getQuantity(): int;
    public function getImages(): iterable;
    public function getTitle(): string;
    public function getDescription(): ?string;

    /** @return CartDiscount[] */
    public function getDiscounts(): iterable;
}