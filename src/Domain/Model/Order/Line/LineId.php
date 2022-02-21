<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Line;

final class LineId
{
    private int $lineId;

    private function __construct(int $lineId)
    {
        $this->lineId = $lineId;
    }

    public static function fromInt(int $lineId): self
    {
        return new static($lineId);
    }

    public function asInt(): int
    {
        return $this->lineId;
    }
}
