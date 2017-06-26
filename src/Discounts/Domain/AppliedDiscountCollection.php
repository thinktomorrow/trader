<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

class AppliedDiscountCollection implements \ArrayAccess, \Countable, \IteratorAggregate
{
    private $discounts = [];

    public function __construct(AppliedDiscount ...$discounts)
    {
        if(!$discounts) return $this;

        foreach($discounts as $discount) $this->add($discount);
    }

    public function all(): array
    {
        return $this->discounts;
    }

    public function any(): bool
    {
        return ! empty($this->discounts);
    }

    public function size(): int
    {
        return count($this->discounts);
    }

    public function add(AppliedDiscount $discount): self
    {
        $this->discounts[] = $discount;

        return $this;
    }

    public function offsetExists($offset)
    {
        if(!is_string($offset) && !is_int($offset)) return false;
        return array_key_exists($offset, $this->discounts);
    }

    public function offsetGet($offset)
    {
        return $this->discounts[$offset];
    }

    /**
     * Add discount to cart collection.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if(is_null($offset))
        {
            throw new \InvalidArgumentException('Adding discount to cart requires an explicit key. This key is the discount identifier.');
        }

        if( ! $value instanceof AppliedDiscount)
        {
            throw new \InvalidArgumentException('Adding discount to order requires an instance of '. AppliedDiscount::class.' to be passed. '. gettype($value). ' given.');
        }

        $this->discounts[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->discounts[$offset]);
    }

    public function count()
    {
        return count($this->discounts);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->discounts);
    }

}