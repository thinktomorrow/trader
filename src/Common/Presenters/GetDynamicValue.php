<?php

namespace Thinktomorrow\Trader\Common\Presenters;

use Thinktomorrow\MagicAttributes\HasMagicAttributes;

trait GetDynamicValue
{
    use HasMagicAttributes;

    public function getValue($key, $default = null, $closure = null)
    {
        return $this->attr('values.' . $key, $default, $closure);
    }

    public function __get($name)
    {
        if (method_exists($this, $name)) {
            return $this->{$name}();
        }

        // Convert snakecase to Camelcase e.g. tax_percentage value should trigger taxPercentage() method
        if (method_exists($this, $this->snakeToCamelcase($name))) {
            return $this->{$this->snakeToCamelcase($name)}();
        }

        return $this->getValue($name, null);
    }

    /**
     * Catch fake methods such as brand() which refers to the value brand.
     *
     * @param $method
     * @param $args
     *
     * @return mixed|null
     */
    public function __call($method, $args)
    {
        // Only assume fake methods if they are passed no arguments
        if (!$args) {
            return $this->getValue($method);
        }

        throw new \RuntimeException('Unknown method '.$method);
    }

    private function snakeToCamelcase($value)
    {
        return str_replace('_', '', ucwords($value, '_'));
    }
}
