<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

final class LineNumber
{
    private int $lineNumber;

    private function __construct(int $lineNumber)
    {
        $this->lineNumber = $lineNumber;
    }

    public static function fromInt(int $lineNumber): self
    {
        return new static($lineNumber);
    }

    public function asInt(): int
    {
        return $this->lineNumber;
    }
}
