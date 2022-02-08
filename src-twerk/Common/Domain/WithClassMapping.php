<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Common\Domain;

trait WithClassMapping
{
    protected string $key;

    /**
     * This requires the class to have a mapping array of the following kind:.
     *
     *  protected static $mapping = [
     *       key => classname
     *  ];
     *
     * @param string $key
     */
    private function __construct(string $key)
    {
        $this->key = $key;
    }

    public static function fromKey(string $key)
    {
        if (! isset(static::getMapping()[$key])) {
            throw new \InvalidArgumentException('Invalid key ['.$key.']. Not found as available class mapping.');
        }

        return new static($key);
    }

    abstract protected static function getMapping(): array;

    public static function fromClass($instance)
    {
        if (false === ($key = array_search(get_class($instance), static::getMapping()))) {
            throw new \InvalidArgumentException('Class ['.get_class($instance).'] not found as available type string.');
        }

        return new static($key);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function __toString(): string
    {
        return $this->key;
    }

    public function getClass(): string
    {
        return static::getMapping()[$this->key];
    }

    public function equalsKey(string $key): bool
    {
        return $this->key === $key;
    }

    public function equalsClass($other): bool
    {
        return get_class($other) === $this->getClass();
    }

    public function equals($other): bool
    {
        return get_class($other) === get_class($this)
            && (string) $this === (string) $other;
    }
}
