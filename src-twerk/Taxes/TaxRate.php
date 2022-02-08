<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Taxes;

use Thinktomorrow\Trader\Common\Cash\Percentage;

class TaxRate
{
    private Percentage $percentage;

    private function __construct(Percentage $percentage)
    {
        $this->percentage = $percentage;
    }

    public static function fromInteger(int $rate): self
    {
        return new static(Percentage::fromInteger($rate));
    }

    public static function default(): self
    {
        return static::fromInteger(app()->make('trader_config')->defaultTaxRate());
    }

    public function toPercentage(): Percentage
    {
        return $this->percentage;
    }
}
