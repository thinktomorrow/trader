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
     * Get parameter values
     *
     * @return array
     */
    public function getParameters(): array;

    /**
     * Get parameter values as normalized values, ready for form input
     * Objects are transposed to their primitive values.
     *
     * @return array
     */
    public function getParameterValues(): array;

    /**
     * Set parameter values from normalized values
     *
     * @param array|mixed $values
     * @return Condition
     */
    public function setParameterValues($values): Condition;
}
