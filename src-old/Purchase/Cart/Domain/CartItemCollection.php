<?php declare(strict_types=1);

namespace Purchase\Cart\Domain;

use Purchase\Items\Domain\PurchasableItemId;

interface CartItemCollection
{
    public function isEmpty(): bool;

    public function size(): int;

    public function count(): int;

    /**
     * Quantity across all items
     *
     * @return int
     */
    public function quantity(): int;

    /**
     * Grouped representation of all item discounts.
     * For each discount, the totals are added up
     *
     * @return array
     */
    public function discounts(): array;

    public function find(string $cartItemId): ?CartItem;

    public function findByPurchasable(PurchasableItemId $purchasableItemId): ?CartItem;

    public function add(CartItem $item, $quantity = 1);

    public function addMany(array $items);

    public function replaceItem(string $cartItemId, $quantity);

    public function removeItem(string $cartItemId);

    public function offsetSet($offset, $value);

    public function all(): array;
}
