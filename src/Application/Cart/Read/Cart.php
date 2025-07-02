<?php

namespace Thinktomorrow\Trader\Application\Cart\Read;

use Money\Money;

interface Cart
{
    public static function fromMappedData(array $state, array $childObjects, array $discounts): static;

    public function getOrderId(): string;

    /** @return CartLine[] */
    public function getLines(): iterable;

    public function isEmpty(): bool;

    /** The amount of different items */
    public function getSize(): int;

    /** The quantity of all items combined */
    public function getQuantity(): int;

    public function getTotalPrice(?bool $includeTax = null): string;

    public function getSubtotalPrice(?bool $includeTax = null): string;

    public function getShippingCost(?bool $includeTax = null): ?string;

    public function getPaymentCost(?bool $includeTax = null): ?string;

    public function getDiscountPrice(?bool $includeTax = null): ?string;

    public function getTaxPrice(): string;

    public function getTotalPriceAsMoney(?bool $includeTax = null): Money;

    public function getSubtotalPriceAsMoney(?bool $includeTax = null): Money;

    public function getShippingCostAsMoney(?bool $includeTax = null): Money;

    public function getPaymentCostAsMoney(?bool $includeTax = null): Money;

    public function getDiscountPriceAsMoney(?bool $includeTax = null): Money;

    public function getTaxPriceAsMoney(): Money;

    public function includeTax(bool $includeTax = true): void;

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
}
