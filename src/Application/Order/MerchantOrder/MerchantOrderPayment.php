<?php

namespace Thinktomorrow\Trader\Application\Order\MerchantOrder;

interface MerchantOrderPayment
{
    public static function fromMappedData(array $state, array $orderState, iterable $discounts): static;

    public function getPaymentId(): string;
    public function getPaymentMethodId(): ?string;
    public function getPaymentState(): string;
    public function getCostPrice(): string;
    public function includeTax(bool $includeTax = true): void;

    /** @return MerchantOrderDiscount[] */
    public function getDiscounts(): iterable;

    public function getTitle(): ?string;
    public function getDescription(): ?string;
}
