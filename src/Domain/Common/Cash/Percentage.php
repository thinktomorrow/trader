<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Cash;

use Assert\Assertion;

class Percentage
{
    private string $value;

    private function __construct(string $value)
    {
        Assertion::greaterOrEqualThan($value, 0);

        $this->value = $value;
    }

    public static function fromString(string $percent): self
    {
        return new static($percent);
    }

    public static function zero(): self
    {
        return new static("0");
    }

    public function get(): string
    {
        return $this->value;
    }

    public function toDecimal(): string
    {
        return bcdiv($this->value, "100", 4);
    }

    public function doesItExceeds100(): bool
    {
        return $this->value > 100;
    }

    public function equals($other): bool
    {
        return (get_class($other) === get_class($this) && $other->value === $this->value);
    }

    public function __toString(): string
    {
        return $this->get();
    }
}
