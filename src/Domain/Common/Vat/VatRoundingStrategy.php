<?php

namespace Thinktomorrow\Trader\Domain\Common\Vat;

enum VatRoundingStrategy: string
{
    case unit_based = 'unit_based';
    case line_based = 'line_based';

    public static function getDefault(): self
    {
        return self::line_based;
    }

    public static function fromString(string $strategy): self
    {
        return self::fromStringOrDefault($strategy);
    }

    public static function fromStringOrDefault(mixed $strategy): self
    {
        if (! is_string($strategy) || $strategy === '') {
            return self::getDefault();
        }

        return self::tryFrom($strategy) ?? self::getDefault();
    }

    public function isLineBased(): bool
    {
        return $this === self::line_based;
    }
}
