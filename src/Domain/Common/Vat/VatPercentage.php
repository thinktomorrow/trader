<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Vat;

use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;

class VatPercentage
{
    private Percentage $percentage;

    private function __construct(Percentage $percentage)
    {
        $this->percentage = $percentage;
    }

    public static function fromString(string $rate): self
    {
        if (! preg_match('/^\d+(?:\.\d{1,6})?$/', $rate)) {
            throw new \InvalidArgumentException('VAT percentage must be a non-negative decimal with at most six decimal places.');
        }

        return new static(Percentage::fromString($rate));
    }

    public static function zero(): self
    {
        return new static(Percentage::zero());
    }

    public function toPercentage(): Percentage
    {
        return $this->percentage;
    }

    public function equals($other): bool
    {
        return get_class($other) === get_class($this) && $other->percentage->equals($this->percentage);
    }

    public function get(): string
    {
        return $this->percentage->get();
    }

    public function __toString(): string
    {
        return $this->percentage->get();
    }
}
