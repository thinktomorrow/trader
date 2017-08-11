<?php

namespace Thinktomorrow\Trader\Common\Domain\Price;

class Percentage
{
    private $value;

    private function __construct($value)
    {
        $this->value = $value;
    }

    // We don't encourage to set fromFloat because this will give us crazy results
    public static function fromPercent($percent)
    {
        // TODO: validate either integer or decimal with 2 decimals max.
        // Float should not be allowed!

        return new self($percent);
    }

    public function asFloat(): float
    {
        return $this->value / 100;
    }

    public function asPercent()
    {
        return $this->value;
    }

    public function isPositive():bool
    {
        return $this->value > 0;
    }
}