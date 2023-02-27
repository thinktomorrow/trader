<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\State;

interface State
{
    public function getValueAsString(): string;

    public function equals($other): bool;

    public static function fromString(string $state): self;

    public static function getDefaultState(): self;

    public static function getStates(): array;

    public static function getTransitions(): array;

    public static function getEventMapping(): array;
}
