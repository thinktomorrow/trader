<?php

namespace Thinktomorrow\Trader\Application\Order\MerchantOrder;

use Money\Money;

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

    public function getSubtotalExcl(): Money;

    public function getSubtotalIncl(): Money;

    public function getShippingCostExcl(): Money;

    public function getShippingCostIncl(): Money;

    public function getPaymentCostExcl(): Money;

    public function getPaymentCostIncl(): Money;

    public function getDiscountTotalExcl(): Money;

    public function getDiscountTotalIncl(): Money;

    public function getTotalExcl(): Money;

    public function getTotalVat(): Money;

    public function getTotalIncl(): Money;

    public function getFormattedSubtotalExcl(): string;

    public function getFormattedSubtotalIncl(): string;

    public function getFormattedShippingCostExcl(): string;

    public function getFormattedShippingCostIncl(): string;

    public function getFormattedPaymentCostExcl(): string;

    public function getFormattedPaymentCostIncl(): string;

    public function getFormattedDiscountTotalExcl(): string;

    public function getFormattedDiscountTotalIncl(): string;

    public function getFormattedTotalExcl(): string;

    public function getFormattedTotalVat(): string;

    public function getFormattedTotalIncl(): string;

    public function getShopper(): MerchantOrderShopper;

    /** @return MerchantOrderShipping[] */
    public function getShippings(): array;

    public function findShipping(string $shippingId): ?MerchantOrderShipping;

    /** @return MerchantOrderPayment[] */
    public function getPayments(): array;

    public function findPayment(string $paymentId): ?MerchantOrderPayment;

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

    public function getOrderEvents(): iterable;

    public function getData(string $key, ?string $language = null, $default = null);
}
