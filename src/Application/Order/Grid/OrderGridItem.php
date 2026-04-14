<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\Grid;

use Money\Money;

interface OrderGridItem
{
    public static function fromMappedData(array $state, array $shopperState): static;

    public function getOrderId(): string;

    public function getOrderReference(): string;

    public function getInvoiceReference(): ?string;

    public function getOrderState(): string;

    public function getUpdatedAt(): ?\DateTime;

    public function getConfirmedAt(): ?\DateTime;

    public function getPaidAt(): ?\DateTime;

    public function getDeliveredAt(): ?\DateTime;

    public function getTitle(): string;

    public function getDescription(): string;

    public function getUrl(): string;

    public function getShopperTitle(): string;

    public function hasCustomer(): bool;

    public function getCustomerUrl(): ?string;

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
}
