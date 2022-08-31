<?php

namespace Thinktomorrow\Trader\Domain\Common\Map;

trait HasSimpleMapping
{
    private static array $map = [];

    public static function get(): array
    {
        return static::$map;
    }

    public static function set(array $map): void
    {
        static::$map = $map;
    }
}
