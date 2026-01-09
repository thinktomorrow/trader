<?php

namespace Thinktomorrow\Trader\Application\Cart\Read;

use Money\Money;

interface Cart
{
    public static function fromMappedData(array $state, array $childObjects, array $discounts): static;

    public function getOrderId(): string;

    /** @return CartLine[] */
    public function getLines(): iterable;

    public function findLine(string $lineId): ?CartLine;

    public function isEmpty(): bool;

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

    public function isVatExempt(): bool;

    public function getShopper(): ?CartShopper;

    public function getShipping(): ?CartShipping;

    public function getPayment(): ?CartPayment;

    public function getShippingAddress(): ?CartShippingAddress;

    public function getBillingAddress(): ?CartBillingAddress;

    /** @return CartDiscount[] */
    public function getDiscounts(): iterable;

    public function getEnteredCoupon(): ?string;

    /**
     * The combined collection of applied discounts.
     * Next to the order discounts, this also includes line, shipping or payment discounts.
     *
     * @return CartDiscount[]
     */
    public function getAllDiscounts(): iterable;

    public function getData(?string $key = null, $default = null): mixed;
}
