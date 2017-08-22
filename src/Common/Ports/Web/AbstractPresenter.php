<?php

namespace Thinktomorrow\Trader\Common\Ports\Web;

abstract class AbstractPresenter
{
    use GetDynamicValue;

    protected $values;

    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    public function __set($name, $value)
    {
        $this->values[$name] = $value;
    }
}
