<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test;

use Psr\Container\ContainerInterface;

final class TestContainer implements ContainerInterface
{
    private static array $entries;

    public function add(string $id, $entry)
    {
        static::$entries[$id] = $entry;
    }

    public function get(string $id)
    {
        if (! $this->has($id)) {
            return new $id;
        }

        return static::$entries[$id];
    }

    public function has(string $id): bool
    {
        return isset(static::$entries[$id]);
    }

    public static function make(string $id)
    {
        return (new static)->get($id);
    }
}
