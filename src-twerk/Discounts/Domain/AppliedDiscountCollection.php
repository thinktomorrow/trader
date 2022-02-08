<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Discounts\Domain;

use ArrayIterator;

class AppliedDiscountCollection implements \IteratorAggregate, \Countable
{
    private array $items = [];

    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    public function isEmpty(): bool
    {
        return $this->count() < 1;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function findItem(DiscountId $discountId): ?AppliedDiscount
    {
        if (! isset($this->items[$discountId->get()])) {
            return null;
        }

        return $this->items[$discountId->get()];
    }

    public function addItem(AppliedDiscount $item): void
    {
        if (isset($this->items[$item->getId()->get()])) {
            return;
        }

        $this->items[$item->getId()->get()] = $item;
    }

    public function replaceItem(AppliedDiscount $item): void
    {
        $this->items[$item->getId()->get()] = $item;
    }

    public function removeItem(DiscountId $discountId): void
    {
        if (! isset($this->items[$discountId->get()])) {
            throw new \InvalidArgumentException('Cannot remove item. Order does not contain an item by id ['.$discountId->get().']');
        }

        unset($this->items[$discountId->get()]);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }
}
