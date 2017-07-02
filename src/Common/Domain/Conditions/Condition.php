<?php

namespace Thinktomorrow\Trader\Common\Domain\Conditions;

use Thinktomorrow\Trader\Order\Domain\Order;

interface Condition
{
    /**
     * Set the values required to check the condition against the order
     *
     * @param array $parameters
     * @return mixed
     */
    public function setParameters(array $parameters);
}