<?php

namespace Thinktomorrow\Trader\Common\Helpers;

trait ResolvesFromContainer
{
    /**
     * @param $class
     * @param array $parameters
     *
     * @return mixed
     */
    protected function resolve($class, $parameters = [])
    {
        if (!is_array($parameters)) {
            $parameters = [$parameters];
        }

        return $this->container->make($class, $parameters);
    }
}
