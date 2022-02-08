<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Ports;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Thinktomorrow\Trader\Order\Domain\OrderProduct;
use Thinktomorrow\Trader\Order\Domain\OrderProductCollection;

class DbOrderProductCollection implements OrderProductCollection, Countable, IteratorAggregate
{
    private array $items = [];

    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->addItem($item, $item->getQuantity());
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

    /**
     * Quantity across all items
     * @return int
     */
    public function quantity(): int
    {
        $quantity = 0;

        /** @var DefaultOrderProduct $item */
        foreach ($this->items as $item) {
            $quantity += $item->getQuantity();
        }

        return $quantity;
    }

//    /**
//     * Grouped representation of all item discounts.
//     * For each discount, the totals are added up
//     *
//     * @return array
//     */
//    public function discounts(): array
//    {
//        $discounts = collect();
//
//        foreach($this->items as $item) {
//            /** @var AppliedDiscount $itemDiscount */
//            foreach($item->discounts() as $itemDiscount) {
//                $key = $itemDiscount->getId()->get();
//
//                if(!isset($discounts[$key])){
//                    $discounts[$key] = $itemDiscount;
//                    continue;
//                }
//
//                $discounts[$key] = new AppliedDiscount(
//                    $itemDiscount->getId(),
//                    $itemDiscount->type(),
//                    $discounts[$key]->total()->add($itemDiscount->total()),
//                    $discounts[$key]->taxRate(),
//                    $discounts[$key]->baseTotal()->add($itemDiscount->baseTotal()),
//                    $itemDiscount->percentage(),
//                    $itemDiscount->toArray()['data']
//                );
//            }
//        }
//
//        return $discounts;
//    }

    public function findItem(string $orderProductId): ?OrderProduct
    {
        if (! isset($this->items[$orderProductId])) {
            return null;
        }

        return $this->items[$orderProductId];
    }

    public function addItem(OrderProduct $item, $quantity = 1): void
    {
        if (isset($this->items[$item->getId()])) {
            $quantity += $this->items[$item->getId()]->getQuantity();
            $this->items[$item->getId()]->replaceQuantity($quantity);

            return;
        }

        $this->items[$item->getId()] = $item;
        $this->items[$item->getId()]->replaceQuantity($quantity);
    }

    public function replaceItem(string $orderProductId, $quantity): void
    {
        if (! isset($this->items[$orderProductId])) {
            throw new \InvalidArgumentException('Cart does not contain given item by id ['.$orderProductId.']');
        }

        if ($quantity < 1) {
            $this->removeItem($orderProductId);
        }

        $this->findItem($orderProductId)->replaceQuantity($quantity);
    }

    public function removeItem(string $orderProductId): void
    {
        if (! isset($this->items[$orderProductId])) {
            throw new \InvalidArgumentException('Cannot remove item. Order does not contain an item by id ['.$orderProductId.']');
        }

        $this->findItem($orderProductId)->replaceQuantity(0);
        unset($this->items[$orderProductId]);
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
