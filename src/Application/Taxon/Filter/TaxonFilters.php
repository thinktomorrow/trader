<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Filter;

use Countable;
use Traversable;
use Assert\Assertion;
use IteratorAggregate;

class TaxonFilters implements Countable, IteratorAggregate, \ArrayAccess
{
    private array $items;

    public function __construct(array $items)
    {
        Assertion::allIsInstanceOf($items, TaxonFilter::class);

        $this->items = $items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    // TODO: flatten() -> for listing of active filters?

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
}
