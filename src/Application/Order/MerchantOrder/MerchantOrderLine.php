<?php

namespace Thinktomorrow\Trader\Application\Order\MerchantOrder;

interface MerchantOrderLine
{
    public static function fromMappedData(array $state, array $orderState, iterable $discounts): static;

    public function getLineId(): string;
    public function getVariantId(): string;
    public function getProductId(): string;

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

    /** @return MerchantOrderDiscount[] */
    public function getDiscounts(): iterable;
}
