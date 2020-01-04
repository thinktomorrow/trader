<?php

namespace Optiphar\Cart;

use Assert\Assertion;
use Illuminate\Support\Collection;

class CartItems extends Collection implements \ArrayAccess, \Countable, \IteratorAggregate
{
    public function __construct(array $items = [])
    {
        Assertion::allIsInstanceOf($items, CartItem::class);

        foreach($items as $item){
            $this->add($item, $item->quantity());
        }
    }

   public function size(): int
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
     * @return Collection
     */
    public function discounts(): Collection
    {
        $discounts = collect();

        foreach($this->items as $item) {
            /** @var CartDiscount $itemDiscount */
            foreach($item->discounts() as $itemDiscount) {
                $key = $itemDiscount->id()->get();

                if(!isset($discounts[$key])){
                    $discounts[$key] = $itemDiscount;
                    continue;
                }

                $discounts[$key] = new CartDiscount(
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

    public function find($cartItemId): ?CartItem
    {
        if (!isset($this->items[$cartItemId])) {
            return null;
        }

        return $this->items[$cartItemId];
    }

    public function findByProduct($productId): ?CartItem
    {
        foreach($this->items as $item) {
            if($item->productId() == $productId) {
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

    public function replace($cartItemId, $quantity)
    {
        if (!isset($this->items[$cartItemId])) {
            throw new \InvalidArgumentException('Cart does not contain given item by id ['.$cartItemId.']');
        }

        if ($quantity < 1) {
            return $this->remove($cartItemId);
        }

        $this->find($cartItemId)->setQuantity($quantity);
    }

    public function remove($cartItemId)
    {
        if (!isset($this->items[$cartItemId])) {
            throw new \InvalidArgumentException('Order does not contain given item by id ['.$cartItemId.']');
        }

        $this->find($cartItemId)->setQuantity(0);
        unset($this->items[$cartItemId]);
    }

    public function removeItemsAddedAsFreeItemDiscount()
    {
        /** @var CartItem $item */
        foreach($this->items as $item){
            if($item->isAddedAsFreeItemDiscount()) {
                $this->remove($item->id());
            }
        }
    }

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
}
