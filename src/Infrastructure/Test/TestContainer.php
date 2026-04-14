<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test;

use Psr\Container\ContainerInterface;

final class TestContainer implements ContainerInterface
{
    private static array $entries;

    public function add(string $id, $entry)
    {
        self::$entries[$id] = $entry;
    }

    public function get(string $id)
    {
        if (! $this->has($id)) {
            return new $id;
        }

        return self::$entries[$id];
    }

    public function has(string $id): bool
    {
        return isset(self::$entries[$id]);
    }

    public static function make(string $id)
    {
        return (new self)->get($id);
    }
}
