<?php

namespace Thinktomorrow\Trader\Common\Domain\Conditions;

abstract class BaseCondition
{
    protected $parameters = [];

    public function setParameters(array $parameters): self
    {
        if (method_exists($this, 'validateParameters')) {
            $this->validateParameters($parameters);
        }

        $this->parameters = $parameters;

        return $this;
    }
}
