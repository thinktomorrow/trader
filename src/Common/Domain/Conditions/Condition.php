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
    public function setParameters(array $parameters): Condition;

    /**
     * Get parameter values as normalized values, ready for form input
     *
     * @return mixed
     */
    public function getParameterValues(): array;

    /**
     * Set parameter values from normalized values
     *
     * @return mixed
     */
    public function setParameterValues(array $values): Condition;
}
