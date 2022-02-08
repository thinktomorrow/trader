<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Options\Ports;

use Thinktomorrow\Trader\Catalog\Options\Domain\Option;

class Options implements \IteratorAggregate, \Thinktomorrow\Trader\Catalog\Options\Domain\Options, \Countable, \ArrayAccess
{
    private array $options;

    public function __construct(\Thinktomorrow\Trader\Catalog\Options\Domain\Option ...$options)
    {
        $this->options = $options;
    }

    public function getIds(): array
    {
        $ids = [];

        foreach ($this->options as $option) {
            $ids[] = $option->getId();
        }

        return $ids;
    }

    public function findById(string $id): Option
    {
        foreach ($this->options as $option) {
            if ($option->getId() === $id) {
                return $option;
            }
        }

        throw new \InvalidArgumentException('No Option found by id ' . $id);
    }

    public function grouped(): array
    {
        return collect($this->options)
            ->groupBy(fn (\Thinktomorrow\Trader\Catalog\Options\Domain\Option $option) => $option->getOptionTypeId())
            ->values()
            ->toArray();
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->options);
    }

    public function count()
    {
        return count($this->options);
    }

    public function offsetExists($offset)
    {
        return isset($this->options[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->options[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->options[] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->options[$offset]);
    }
}
