<?php

namespace Thinktomorrow\Trader\Common\Helpers;

trait HandlesKeyToClassMapping
{
    /**
     * This requires the class to have a mapping array of the following kind:
     *
     *  protected static $mapping = [
     *       // key => classname
     *  ];
     */

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function fromString(string $type)
    {
        if (!isset(static::$mapping[$type])) {
            throw new \InvalidArgumentException('Invalid type [' . $type . ']. Not found as available class mapping.');
        }

        return new static($type);
    }

    public static function fromInstance($instance)
    {
        if (false === ($key = array_search(get_class($instance), static::$mapping))) {
            throw new \InvalidArgumentException('Class [' . get_class($instance) . '] not found as available type string.');
        }

        return new self($key);
    }

    public function get(): string
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return $this->type;
    }

    public function class(): string
    {
        return static::$mapping[$this->type];
    }

    public function equals($other): bool
    {
        return get_class($other) === get_class($this)
            && (string)$this === (string)$other;
    }

    public function equalsClass($other): bool
    {
        return get_class($other) === $this->class();
    }
}
