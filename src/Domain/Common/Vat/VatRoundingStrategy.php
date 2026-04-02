<?php

namespace Thinktomorrow\Trader\Domain\Common\Vat;

enum VatRoundingStrategy: string
{
    case unit_based = 'unit_based';
    case line_based = 'line_based';

    public static function fromString(string $strategy): self
    {
        return self::tryFrom($strategy) ?? self::unit_based;
    }
}
