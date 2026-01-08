<?php

namespace Thinktomorrow\Trader\Application\Cart\Read;

use Money\Money;

interface CartPayment
{
    public static function fromMappedData(array $state, array $cartState, iterable $discounts): static;

    public function getPaymentId(): string;

    public function getPaymentMethodId(): string;

    public function getProviderId(): string;

    public function getCostPriceExcl(): Money;

    public function getDiscountPriceExcl(): Money;

    public function getTotalPriceExcl(): Money;

    public function getFormattedCostPriceExcl(): string;

    public function getFormattedDiscountPriceExcl(): string;

    public function getFormattedTotalPriceExcl(): string;

    /** @return CartDiscount[] */
    public function getDiscounts(): iterable;

    public function getTitle(): ?string;

    public function getDescription(): ?string;
}
