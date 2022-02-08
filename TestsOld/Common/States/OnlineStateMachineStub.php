<?php

namespace Thinktomorrow\Trader\Tests\Common\States;

use Common\Domain\States\StateMachine;

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
