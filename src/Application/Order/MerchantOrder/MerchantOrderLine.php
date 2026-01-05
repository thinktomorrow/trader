<?php

namespace Thinktomorrow\Trader\Application\Order\MerchantOrder;

use Money\Money;
use Thinktomorrow\Trader\Domain\Model\Order\Line\PurchasableReference;

interface MerchantOrderLine
{
    public static function fromMappedData(array $state, array $orderState, iterable $discounts, iterable $personalisations): static;

    public function getLineId(): string;

    public function getPurchasableReference(): PurchasableReference;

    /**
     * Any purchasasble can be a line, but still we provide easy access to
     * the most common ones: variant and product.
     */
    public function getVariantId(): ?string;

    public function getProductId(): ?string;

    public function getUnitPriceExcl(): Money;

    public function getUnitPriceIncl(): Money;

    public function getDiscountPriceExcl(): Money;

    public function getDiscountPriceIncl(): Money;

    public function getTotalPriceExcl(): Money;

    public function getTotalVat(): Money;

    public function getTotalPriceIncl(): Money;

    public function getFormattedUnitPriceExcl(): string;

    public function getFormattedUnitPriceIncl(): string;

    public function getFormattedDiscountPriceExcl(): string;

    public function getFormattedDiscountPriceIncl(): string;

    public function getFormattedTotalPriceExcl(): string;

    public function getFormattedTotalPriceIncl(): string;

    public function getFormattedSubtotalPriceExcl(): string;

    public function getFormattedSubtotalPriceIncl(): string;

    public function getFormattedTotalVat(): string;

    public function getFormattedVatRate(): string;

    public function getQuantity(): int;

    public function getTitle(): string;

    public function getDescription(): ?string;

    public function setImages(iterable $images): void;

    public function getImages(): iterable;

    /** @return MerchantOrderDiscount[] */
    public function getDiscounts(): iterable;

    /** @return MerchantOrderLinePersonalisation[] */
    public function getPersonalisations(): iterable;

    public function getData(?string $key = null, $default = null): mixed;
}
