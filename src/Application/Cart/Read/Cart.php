<?php

namespace Thinktomorrow\Trader\Application\Cart\Read;

use Money\Money;

interface Cart
{
    public static function fromMappedData(array $state, array $childObjects, iterable $discounts): static;

    public function getOrderId(): string;

    /** @return CartLine[] */
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

    public function getShopper(): ?CartShopper;
    public function getShipping(): ?CartShipping;
    public function getPayment(): ?CartPayment;
    public function getShippingAddress(): ?CartShippingAddress;
    public function getBillingAddress(): ?CartBillingAddress;

    /** @return CartDiscount[] */
    public function getDiscounts(): iterable;
}
