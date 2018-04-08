<?php

namespace Thinktomorrow\Trader\Common\Presenters;

trait GetDynamicValue
{
    public function getValue($key, $default = null, $closure = null)
    {
        if (isset($this->values[$key])) {
            $value = $this->values[$key];
        } else {
            // If key is not found as is, we assume we want to find a nested value
            if (false === strpos($key, '.')) {
                // Replace camelCase with dot syntax
                $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '.$0', $key));
            }

            // At this point no nesting we want to give back the default
            if (false === strpos($key, '.')) {
                return $default;
            }

            $keys = explode('.', $key);

            $value = $this->getValue(array_shift($keys));
            foreach ($keys as $nestedKey) {
                // Normalize to array
                if (is_object($value)) {
                    $value = (array) $value;
                }

                if (!isset($value[$nestedKey])) {
                    return $default;
                }

                $value = $value[$nestedKey];
            }
        }

        return is_callable($closure)
            ? call_user_func_array($closure, [$value, $this])
            : $value;
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
