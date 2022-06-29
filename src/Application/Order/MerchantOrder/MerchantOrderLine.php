<?php

namespace Thinktomorrow\Trader\Application\Order\MerchantOrder;

interface MerchantOrderLine
{
    public static function fromMappedData(array $state, array $orderState, iterable $discounts): static;

    public function getLineId(): string;
    public function getVariantId(): string;

    public function getLinePrice(): string;
    public function getTotalPrice(): string;
    public function getSubtotalPrice(): string;
    public function getTaxPrice(): string;
    public function getDiscountPrice(): string;
    public function includeTax(bool $includeTax = true): void;

    public function getQuantity(): int;

    public function getTitle(): string;
    public function getDescription(): ?string;
    public function setImage(string $image): void;
    public function getImage(): ?string;

    /** @return MerchantOrderDiscount[] */
    public function getDiscounts(): iterable;
}
