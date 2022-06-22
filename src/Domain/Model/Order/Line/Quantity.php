<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Line;

use Assert\Assertion;

final class Quantity
{
    private int $quantity;

    private function __construct(int $quantity)
    {
        Assertion::greaterOrEqualThan($quantity, 0);

        $this->quantity = $quantity;
    }

    public static function fromInt(int $quantity): self
    {
        return new static($quantity);
    }

    public function asInt(): int
    {
        return $this->quantity;
    }
}
