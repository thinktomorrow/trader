<?php

namespace Thinktomorrow\Trader\Common\Contracts;

interface HasParameters
{
    /**
     * Get parameter values.
     *
     * @return array
     */
    public function getParameters(): array;

    /**
     * Get specific parameter value. If key is omitted, the first
     * parameter value should be returned.
     *
     * @param string|null $key
     *
     * @return mixed
     */
    public function getParameter(string $key = null);

    /**
     * Sets the parameters required to check the condition against the order.
     * The expected format is ['parameter_key' => 'parameter_value] but
     * in case of passing a single parameter, we normalize this to the
     * format by setting the parameter key as the object type.
     *
     * @param mixed $parameters
     *
     * @return HasParameters
     */
    public function setParameters($parameters): self;

    /**
     * Get parameter values as normalized values, ready for form input
     * Objects are transposed to their primitive values.
     *
     * @return array
     */
    public function getRawParameters(): array;

    /**
     * Get specific raw parameter value. If key is omitted, the first
     * parameter value should be returned.
     *
     * @param string|null $key
     *
     * @return mixed
     */
    public function getRawParameter(string $key = null);

    /**
     * Set parameter values from raw, non-object values.
     *
     * @param mixed $values
     *
     * @return HasParameters
     */
    public function setRawParameters($values): self;
}
