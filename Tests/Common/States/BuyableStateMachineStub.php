<?php

namespace Thinktomorrow\Trader\Tests\Common\States;

use Thinktomorrow\Trader\Common\Domain\States\StateMachine;

class BuyableStateMachineStub extends StateMachine
{
    public function __construct(StatefulStub $statefulStub)
    {
        parent::__construct($statefulStub, 'buy_status');
    }
}
