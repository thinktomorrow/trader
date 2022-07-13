<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\Grid;

interface GridItem
{
    public static function fromMappedData(array $state, array $shopperState): static;

    public function getOrderId(): string;
    public function getOrderReference(): string;
    public function getOrderState(): string;
    public function getConfirmedAt(): ?string;
    public function getPaidAt(): ?string;
    public function getDeliveredAt(): ?string;
    public function getTotalPrice(): string;

    public function getTitle(): string;
    public function getDescription(): string;
    public function getUrl(): string;

    public function getShopperTitle(): string;
    public function hasCustomer(): bool;
    public function getCustomerUrl(): ?string;
}
