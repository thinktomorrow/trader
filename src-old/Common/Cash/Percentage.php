<?php

declare(strict_types=1);

namespace Common\Cash;

use Assert\Assertion;

class Percentage
{
    private $value;

    private function __construct($value)
    {
        Assertion::notNull($value);
        Assertion::greaterOrEqualThan($value, 0);

        $this->value = $value;
    }

    /**
     * @param $percent
     * @return Percentage
     */
    public static function fromPercent($percent)
    {
        return new static($percent);
    }

    public function asFloat(): float
    {
        return $this->value / 100;
    }

    public function asPercent()
    {
        return $this->value;
    }

    public function exceeds100(): bool
    {
        return $this->value > 100;
    }

    public function equals($other): bool
    {
        return (get_class($other) === get_class($this) && $other->value ==  $this->value);
    }

    public function __toString()
    {
        return (string) $this->asPercent();
    }
}
