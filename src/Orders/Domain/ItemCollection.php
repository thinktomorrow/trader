<?php

namespace Thinktomorrow\Trader\Orders\Domain;

class ItemCollection implements \ArrayAccess, \Countable, \IteratorAggregate
{
    private $items = [];

    public function __construct(Item ...$items)
    {
        if (!$items) {
            return $this;
        }

        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function all(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function size(): int
    {
        return count($this->items);
    }

    public function first()
    {
        return reset($this->items);
    }

    /**
     * the purchasableID is the reference of an item because cart items are grouped per purchasable
     * while ItemID is specific to a unique item in history and represents the persisted id.
     *
     * @param PurchasableId $id
     *
     * @return mixed|void
     */
    public function find(PurchasableId $id)
    {
        if (!isset($this->items[$id->get()])) {
            return;
        }

        return $this->items[$id->get()];
    }

    public function add(Item $item, $quantity = 1)
    {
        if (isset($this->items[$item->purchasableId()->get()])) {
            $this->items[$item->purchasableId()->get()]->add($quantity);

            return;
        }

        $this->items[$item->purchasableId()->get()] = $item;

        // Quantify newly added item
        if ($quantity > 1) {
            $this->add($item, --$quantity);
        }
    }

    public function addMany(array $items)
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function replace(PurchasableId $purchasableId, $quantity)
    {
        if (!isset($this->items[$purchasableId->get()])) {
            throw new \InvalidArgumentException('Order does not contain given item by id ['.$purchasableId.']');
        }

        // Remove from collection entirely
        if ($quantity < 1) {
            return $this->remove($purchasableId);
        }

        $item = $this->find($purchasableId);
        $item->remove($item->quantity());
        $this->add($item, $quantity);
    }

    public function remove(PurchasableId $purchasableId)
    {
        if (!isset($this->items[$purchasableId->get()])) {
            throw new \InvalidArgumentException('Order does not contain given item by id ['.$purchasableId.']');
        }

        $item = $this->find($purchasableId);
        $item->remove($item->quantity());

        unset($this->items[$purchasableId->get()]);
    }

    public function offsetExists($offset)
    {
        if (!is_string($offset) && !is_int($offset)) {
            return false;
        }

        return array_key_exists($offset, $this->items);
    }

    public function offsetGet($offset)
    {
        return $this->items[$offset];
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

        if (!$value instanceof Item) {
            throw new \InvalidArgumentException('Adding item to cart requires an instance of '.Item::class.' to be given. '.gettype($value).' given.');
        }

        if (isset($this->items[$offset])) {
            // bump count of item instead of adding
            $this->items[$offset]->add(1);

            return;
        }

        $this->items[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    public function count()
    {
        return count($this->items);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }
}
