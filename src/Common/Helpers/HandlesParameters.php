<?php


namespace Thinktomorrow\Trader\Common\Helpers;


use Thinktomorrow\Trader\Common\Contracts\HasParameters;

trait HandlesParameters
{
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter(string $key = null)
    {
        if($key) return $this->parameters[$key];

        return reset($this->parameters);
    }

    public function setParameters($parameters): HasParameters
    {
        /**
         * As a general rule of thumb all the parameters are passed in as an associative array
         * so they can be referenced in the condition logic up ahead. Since most of the
         * conditions require just one parameter, we allow a single value as well.
         */
        $parameters = $this->normalizeParameters($parameters);

        if (method_exists($this, 'validateParameters')) {
            $this->validateParameters($parameters);
        }

        $this->parameters = $parameters;

        return $this;
    }

    public function getRawParameters(): array
    {
        return $this->parameters;
    }

    public function getRawParameter(string $key = null)
    {
        $rawParameters = $this->getRawParameters();

        return ($key) ? $rawParameters[$key] : reset($rawParameters);
    }

    public function setRawParameters($values): HasParameters
    {
        $this->setParameters($this->normalizeParameters($values));

        return $this;
    }

    /**
     * @param mixed $parameters
     * @return array
     */
    protected function normalizeParameters($parameters): array
    {
        if (!is_array($parameters) || !static::isAssocArray($parameters)) {
            $parameters = [$this->type => $parameters];
        }

        return $parameters;
    }
}