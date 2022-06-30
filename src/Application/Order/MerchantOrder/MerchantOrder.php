<?php

namespace Thinktomorrow\Trader\Application\Order\MerchantOrder;

interface MerchantOrder
{
    public static function fromMappedData(array $state, array $childObjects, array $discounts): static;

    public function getOrderId(): string;

    /** @return MerchantOrderLine[] */
    public function getLines(): iterable;

    /** The amount of different items */
    public function getSize(): int;

    /** The quantity of all items combined */
    public function getQuantity(): int;

    public function getTotalPrice(): string;
    public function getSubtotalPrice(): string;
    public function getShippingCost(): ?string;
    public function getPaymentCost(): ?string;
    public function getDiscountPrice(): ?string;
    public function getTaxPrice(): string;
    public function includeTax(bool $includeTax = true): void;

    public function getShopper(): MerchantOrderShopper;
    public function getShipping(): MerchantOrderShipping;
    public function getPayment(): MerchantOrderPayment;
    public function getShippingAddress(): MerchantOrderShippingAddress;
    public function getBillingAddress(): MerchantOrderBillingAddress;

    /** @return MerchantOrderDiscount[] */
    public function getDiscounts(): iterable;
    public function getEnteredCoupon(): ?string;

    /**
     * The combined collection of applied discounts.
     * Next to the order discounts, this also includes line, shipping or payment discounts.
     *
     * @return MerchantOrderDiscount[]
     */
    public function getAllDiscounts(): iterable;
}
