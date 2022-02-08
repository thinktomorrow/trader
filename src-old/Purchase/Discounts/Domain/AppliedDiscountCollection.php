<?php
declare(strict_types=1);

namespace Purchase\Discounts\Domain;

use Assert\Assertion;
use function Thinktomorrow\Trader\Purchase\Discounts\Domain\count;

class AppliedDiscountCollection implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /** @var array */
    private $items;

    public function __construct(array $items = [])
    {
        Assertion::allIsInstanceOf($items, AppliedDiscount::class);

        $this->items = $items;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    public function count()
    {
        return count($this->items);
    }

    public function offsetSet($offset, $value)
    {
        $this->items[$offset] = $value;
    }
}
