<?php

namespace Thinktomorrow\Trader\Common\Presenters;

use Thinktomorrow\Trader\Common\Presenters\GetDynamicValue;

abstract class AbstractPresenter
{
    use Thinktomorrow\Trader\Common\Presenters\GetDynamicValue;

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
