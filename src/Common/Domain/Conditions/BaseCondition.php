<?php

namespace Thinktomorrow\Trader\Common\Domain\Conditions;

use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Order;

abstract class BaseCondition implements Condition
{
    protected $parameters = [];

    /**
     * Unique string identifier of this condition.
     * @var string
     */
    protected $type;

    public function __construct(string $type = null)
    {
        $this->type = $type ?? $this->guessType();
    }

    public function getType(): string
    {
        return $this->type;
    }

    protected function guessType(): string
    {
        $shortName = (new \ReflectionClass($this))->getShortName();

        return static::snakeCase($shortName);
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): Condition
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

    public function getParameterValues(): array
    {
        return $this->parameters;
    }

    public function setParameterValues($values): Condition
    {
        $this->setParameters($this->normalizeParameters($values));

        return $this;
    }

    protected function forOrderDiscount(EligibleForDiscount $eligibleForDiscount): bool
    {
        return $eligibleForDiscount instanceof Order;
    }

    protected function forItemDiscount(EligibleForDiscount $eligibleForDiscount): bool
    {
        return $eligibleForDiscount instanceof Item;
    }

    protected static function isAssocArray($array)
    {
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }

    private static function snakeCase($value, $delimiter = '_')
    {
        if (ctype_lower($value)) return $value;

        $value = preg_replace('/\s+/u', '', ucwords($value));
        return mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
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
