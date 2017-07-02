<?php

namespace Thinktomorrow\Trader\Common\Domain;

class UniqueCollection implements \ArrayAccess, \Countable, \IteratorAggregate
{
    private $items = [];

    public function __construct(array $items = [])
    {
        if(!$items) return $this;

        foreach($items as $item) $this->add($item);
    }

    protected function assertItem($item)
    {
        //
    }

    public function all(): array
    {
        return $this->items;
    }

    public function any(): bool
    {
        return ! empty($this->items);
    }

    public function size(): int
    {
        return count($this->items);
    }

    public function find($id)
    {
        if( !isset($this->items[$id]) ) return null;

        return $this->items[$id];
    }

    public function add($item): self
    {
        $this->assertItem($item);

        $this->items[$item->id()->get()] = $item;

        return $this;
    }

    public function addMany($items): self
    {
        foreach($items as $item) $this->add($item);

        return $this;
    }

    public function offsetExists($offset)
    {
        if(!is_string($offset) && !is_int($offset)) return false;
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
        $this->assertItem($value);

        if(is_null($offset))
        {
            throw new \InvalidArgumentException('Adding item to cart requires an explicit key. This key is the item identifier.');
        }

        if($offset != $value->id())
        {
            throw new \InvalidArgumentException('Key must be set as the item id value. '. $offset. ' is given while ' . $value->id() .' was expected.');
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