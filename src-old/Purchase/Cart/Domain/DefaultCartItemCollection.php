<?php

declare(strict_types=1);

namespace Purchase\Cart\Domain;

use ArrayIterator;
use Assert\Assertion;
use Purchase\Items\Domain\PurchasableItemId;
use Purchase\Discounts\Domain\AppliedDiscount;
use function collect;
use function Thinktomorrow\Trader\Purchase\Cart\Domain\count;
use function Thinktomorrow\Trader\Purchase\Cart\Domain\gettype;

class DefaultCartItemCollection implements \Countable, \IteratorAggregate, CartItemCollection
{
    private $items = [];

    public function __construct(array $items = [])
    {
        Assertion::allIsInstanceOf($items, CartItem::class);

        foreach($items as $item){
            $this->add($item, $item->quantity());
        }
    }

    public function isEmpty(): bool
    {
        return $this->count() < 1;
    }

    public function size(): int
    {
        return $this->count();
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
        foreach($this->items as $item){ $quantity += $item->quantity(); }

        return $quantity;
    }

    /**
     * Grouped representation of all item discounts.
     * For each discount, the totals are added up
     *
     * @return array
     */
    public function discounts(): array
    {
        $discounts = collect();

        foreach($this->items as $item) {
            /** @var AppliedDiscount $itemDiscount */
            foreach($item->discounts() as $itemDiscount) {
                $key = $itemDiscount->id()->get();

                if(!isset($discounts[$key])){
                    $discounts[$key] = $itemDiscount;
                    continue;
                }

                $discounts[$key] = new AppliedDiscount(
                    $itemDiscount->id(),
                    $itemDiscount->type(),
                    $discounts[$key]->total()->add($itemDiscount->total()),
                    $discounts[$key]->taxRate(),
                    $discounts[$key]->baseTotal()->add($itemDiscount->baseTotal()),
                    $itemDiscount->percentage(),
                    $itemDiscount->toArray()['data']
                );
            }
        }

        return $discounts;
    }

    public function find(string $cartItemId): ?CartItem
    {
        if (!isset($this->items[$cartItemId])) {
            return null;
        }

        return $this->items[$cartItemId];
    }

    public function findByPurchasable(PurchasableItemId $purchasableItemId): ?CartItem
    {
        foreach($this->items as $item) {
            if($item->purchasableItemId()->equals($purchasableItemId)) {
                return $item;
            }
        }

        return null;
    }

    public function add(CartItem $item, $quantity = 1)
    {
        if (isset($this->items[$item->id()])) {
            $quantity += $this->items[$item->id()]->quantity();
            $this->items[$item->id()]->setQuantity($quantity);
            return;
        }

        $this->items[$item->id()] = $item;
        $this->items[$item->id()]->setQuantity($quantity);
    }

    public function addMany(array $items)
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function replaceItem(string $cartItemId, $quantity)
    {
        if (!isset($this->items[$cartItemId])) {
            throw new \InvalidArgumentException('Cart does not contain given item by id ['.$cartItemId.']');
        }

        if ($quantity < 1) {
            return $this->removeItem($cartItemId);
        }

        $this->find($cartItemId)->setQuantity($quantity);
    }

    public function removeItem(string $cartItemId)
    {
        if (!isset($this->items[$cartItemId])) {
            throw new \InvalidArgumentException('Cannot remove cartItem. Cart does not contain an item by id ['.$cartItemId.']');
        }

        $this->find($cartItemId)->setQuantity(0);
        unset($this->items[$cartItemId]);
    }

//    public function removeItemsAddedAsFreeItemDiscount()
//    {
//        /** @var CartItem $item */
//        foreach($this->items as $item){
//            if($item->isAddedAsFreeItemDiscount()) {
//                $this->removeItem($item->id());
//            }
//        }
//    }

    /**
     * Add item to cart collection.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            throw new \InvalidArgumentException('Adding item to cart requires an explicit key. This key is the item identifier.');
        }

        if (!$value instanceof CartItem) {
            throw new \InvalidArgumentException('Adding item to cart requires an instance of '.CartItem::class.' to be given. '.gettype($value).' given.');
        }

        $this->items[$offset] = $value;
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
