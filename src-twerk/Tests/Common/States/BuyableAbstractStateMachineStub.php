<?php

namespace Thinktomorrow\Trader\Tests\Common\States;

use Thinktomorrow\Trader\Common\State\AbstractStateMachine;

class BuyableAbstractStateMachineStub extends AbstractStateMachine
{
    protected function getStateKey(): string
    {
        return StatefulStub::BUYABLE_STATEKEY;
    }

    protected function getStates(): array
    {
        return [];
    }

    protected function getTransitions(): array
    {
        return [];
    }

    public function emitEvent(string $transition): void
    {
        // TODO: Implement emitEvent() method.
    }
}
