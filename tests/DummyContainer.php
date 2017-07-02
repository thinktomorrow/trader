<?php

namespace Thinktomorrow\Trader\Tests;

use Psr\Container\ContainerInterface;

/**
 * Class DummyContainer
 */
class DummyContainer implements ContainerInterface
{
    private static $mapping = [];

    public function get($id)
    {
        if(!$this->has($id))
        {
            // Our Dummy container will just try to instantiate the passed id if it's a class
            if(class_exists($id)) return new $id;

            throw new \Exception('Container could not resolve class by identifier ['.$id.']. ');
        }

        $class = self::$mapping[$id];

        return new $class;
    }

    public function has($id)
    {
        return isset(self::$mapping[$id]);
    }

    public function add($id, $value)
    {
        self::$mapping[$id] = $value;
    }
}