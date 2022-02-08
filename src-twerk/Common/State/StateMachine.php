<?php

namespace Thinktomorrow\Trader\Common\State;

interface StateMachine
{
    public function getState(): string;

    /** @throws StateException */
    public function apply(string $transition): void;

    /** @throws StateException */
    public function assertNewState(string $state);

    public function canTransitionTo(string $state): bool;

    public function getAllowedTransitions(): array;

    public function emitEvent(string $transition): void;
}
