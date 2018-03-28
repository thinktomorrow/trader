<?php

namespace Thinktomorrow\Trader\Common\Domain\Conditions;

interface Condition
{
    /**
     * Set the values required to check the condition against the order.
     *
     * @param array $parameters
     *
     * @return mixed
     */
    public function setParameters(array $parameters);

    /**
     * Get parameter values as normalized values, ready for form input
     *
     * @return mixed
     */
    public function getParameterValues(): array;
}
