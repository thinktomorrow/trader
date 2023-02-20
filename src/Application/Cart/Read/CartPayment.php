<?php

namespace Thinktomorrow\Trader\Application\Cart\Read;

interface CartPayment
{
    public static function fromMappedData(array $state, array $cartState, iterable $discounts): static;

    public function getPaymentId(): string;
    public function getPaymentMethodId(): string;
    public function getProviderId(): string;
    public function getCostPrice(): string;
    public function includeTax(bool $includeTax = true): void;

    /** @return CartDiscount[] */
    public function getDiscounts(): iterable;

    public function getTitle(): ?string;
    public function getDescription(): ?string;
}
