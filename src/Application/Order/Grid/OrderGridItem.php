<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\Grid;

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
    public function getTotalPrice(): string;

    public function getTitle(): string;
    public function getDescription(): string;
    public function getUrl(): string;

    public function getShopperTitle(): string;
    public function hasCustomer(): bool;
    public function getCustomerUrl(): ?string;
}
