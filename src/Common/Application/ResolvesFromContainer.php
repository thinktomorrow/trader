<?php


namespace Thinktomorrow\Trader\Common\Application;


trait ResolvesFromContainer
{
    /**
     * @param $class
     * @param array $parameters
     * @return mixed
     */
    protected function resolve($class, $parameters = [])
    {
        if(!is_array($parameters)) $parameters = [$parameters];

        return $this->container->makeWith($class, $parameters);
    }
}