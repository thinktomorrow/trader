<?php

namespace Thinktomorrow\Trader\Tests\Common\States;

use Thinktomorrow\Trader\Common\State\AbstractStateMachine;

class OnlineAbstractStateMachineStub extends AbstractStateMachine
{
    public bool $fakePublishedEventEmitted = false;

    protected function getStateKey(): string
    {
        return StatefulStub::ONLINE_STATEKEY;
    }

    protected function getStates(): array
    {
        return [
            'online',
            'offline',
        ];
    }

    protected function getTransitions(): array
    {
        return [
            'publish' => [
                'from' => ['offline'],
                'to' => 'online',
            ],
            'unpublish' => [
                'from' => ['online'],
                'to' => 'offline',
            ],
        ];
    }

    public function emitEvent(string $transition): void
    {
        if($transition == 'publish') {
            $this->fakePublishedEventEmitted = true;
        }
    }
}
