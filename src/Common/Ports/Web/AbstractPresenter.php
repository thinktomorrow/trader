<?php

namespace Thinktomorrow\Trader\Common\Ports\Web;

abstract class AbstractPresenter
{
    protected $values;

    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    protected function getValue($key, $default = null, $closure = null)
    {
        if(!isset($this->values[$key])) return $default;

        return is_callable($closure)
            ? call_user_func_array($closure,[$this->values[$key],$this])
            : $this->values[$key];
    }

    public function __get($name)
    {
        if(method_exists($this,$name)) return $this->{$name}();

        // Convert snakecase to Camelcase e.g. tax_percentage value should trigger taxPercentage() method
        if(method_exists($this,$this->snakeToCamelcase($name))) return $this->{$this->snakeToCamelcase($name)}();

        return $this->getValue($name,null);
    }

    public function __set($name, $value)
    {
        $this->values[$name] = $value;
    }

    private function snakeToCamelcase($value)
    {
        return str_replace('_','',ucwords($value,'_'));
    }
}