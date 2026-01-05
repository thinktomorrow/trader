<?php

namespace Thinktomorrow\Trader\Application\Order\MerchantOrder;

use Money\Money;

interface MerchantOrderPayment
{
    public static function fromMappedData(array $state, array $orderState, iterable $discounts): static;

    public function getPaymentId(): string;

    public function getPaymentMethodId(): ?string;

    public function getPaymentState(): string;

    public function getCostPriceExcl(): Money;

    public function getDiscountPriceExcl(): Money;

    public function getTotalPriceExcl(): Money;

    public function getFormattedCostPriceExcl(): string;

    public function getFormattedDiscountPriceExcl(): string;

    public function getFormattedTotalPriceExcl(): string;

    /** @return MerchantOrderDiscount[] */
    public function getDiscounts(): iterable;

    public function getTitle(): ?string;

    public function getDescription(): ?string;

    public function getData(string $key, $default = null): mixed;
}
