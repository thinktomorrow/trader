<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Domain;

interface OrderProductCollection
{
    /**
     * Quantity across all items
     * @return int
     */
    public function quantity(): int;

    public function isEmpty(): bool;

    public function findItem(string $orderProductId): ?OrderProduct;

    public function addItem(OrderProduct $item, $quantity = 1): void;

    public function replaceItem(string $orderProductId, $quantity): void;

    public function removeItem(string $orderProductId): void;

    public function all(): array;
}
