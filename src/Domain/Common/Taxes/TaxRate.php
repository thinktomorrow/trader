<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Taxes;

use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;

class TaxRate
{
    private Percentage $percentage;

    private function __construct(Percentage $percentage)
    {
        $this->percentage = $percentage;
    }

    public static function fromString(string $rate): self
    {
        return new static(Percentage::fromString($rate));
    }

    public function toPercentage(): Percentage
    {
        return $this->percentage;
    }

    public function equals($other): bool
    {
        return (get_class($other) === get_class($this) && $other->percentage->equals($this->percentage));
    }

    public function __toString(): string
    {
        return $this->percentage->get();
    }
}
