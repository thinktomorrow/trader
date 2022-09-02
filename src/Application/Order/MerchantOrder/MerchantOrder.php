<?php

namespace Thinktomorrow\Trader\Application\Order\MerchantOrder;

interface MerchantOrder
{
    public static function fromMappedData(array $state, array $childObjects, array $discounts): static;

    public function getOrderId(): string;

    public function getOrderReference(): string;
    public function getInvoiceReference(): ?string;
    public function getState(): string;
    public function getConfirmedAt(): ?\DateTime;
    public function getPaidAt(): ?\DateTime;
    public function getDeliveredAt(): ?\DateTime;

    /** @return MerchantOrderLine[] */
    public function getLines(): iterable;

    /** The amount of different items */
    public function getSize(): int;

    /** The quantity of all items combined */
    public function getQuantity(): int;

    public function getTotalPrice(?bool $includeTax = null): string;
    public function getSubtotalPrice(?bool $includeTax = null): string;
    public function getShippingCost(): ?string;
    public function getPaymentCost(): ?string;
    public function getDiscountPrice(): ?string;
    public function getTaxPrice(): string;
    public function includeTax(bool $includeTax = true): void;

    public function getShopper(): MerchantOrderShopper;

    /** @return MerchantOrderShipping[] */
    public function getShippings(): array;

    /** @return MerchantOrderPayment[] */
    public function getPayments(): array;

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

    public function inCustomerHands(): bool;

    public function getLogEntries(): iterable;
}
