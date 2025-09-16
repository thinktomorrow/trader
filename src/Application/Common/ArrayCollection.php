<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Common;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;

abstract class ArrayCollection implements Countable, IteratorAggregate, ArrayAccess
{
    protected array $items;

    protected function __construct(array $items)
    {
        $this->items = $items;
    }

    abstract public static function fromType(array $items): static;

    public static function empty(): static
    {
        return new static([]);
    }

    public function isEmpty(): bool
    {
        return count($this->items) < 1;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->items[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    public function toArray(): array
    {
        return $this->items;
    }
}
