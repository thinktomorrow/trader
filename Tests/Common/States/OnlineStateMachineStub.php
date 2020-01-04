<?php

namespace Thinktomorrow\Trader\Tests\Common\States;

use Thinktomorrow\Trader\Common\States\StateMachine;

class OnlineStateMachineStub extends StateMachine
{
    public function __construct(StatefulStub $statefulStub)
    {
        parent::__construct($statefulStub, 'online');
    }

    protected $states = [
        true, // online
        false, // offline
    ];

    protected $transitions = [
        'publish' => [
            'from' => [false],
            'to' => true,
        ],
        'unpublish' => [
            'from' => [true],
            'to' => false,
        ],
    ];
}
