<?php

namespace Thinktomorrow\Trader\Unit;

use Thinktomorrow\Trader\Common\State\StateException;
use Thinktomorrow\Trader\Common\State\StatefulContract;
use Thinktomorrow\Trader\Common\State\StateMachine;
use Thinktomorrow\Trader\Tests\TestCase;

class StateMachineTest extends TestCase
{
    private $dummyStatefulContract;
    private $machine;

    public function setUp()
    {
        parent::setUp();

        $this->dummyStatefulContract = new dummyStatefulContract();
        $this->machine = new DummyStateMachine($this->dummyStatefulContract);
    }

    /** @test */
    public function it_can_setup_machine()
    {
        $this->assertInstanceOf(StateMachine::class, $this->machine);
    }

    /** @test */
    public function it_throws_exception_if_transition_map_is_malformed()
    {
        $this->expectException(StateException::class, 'malformed');

        new MalformedStateMachine($this->dummyStatefulContract);
    }

    /** @test */
    public function it_throws_exception_if_transition_contains_invalid_state()
    {
        $this->expectException(StateException::class, 'non existing');

        new MissingStateMachine($this->dummyStatefulContract);
    }

    /** @test */
    public function it_throws_exception_if_applying_unknown_transition()
    {
        $this->expectException(StateException::class, 'unknown transition [unknown] on Thinktomorrow\Trader\Unit\DummyStateMachine');

        $this->machine->apply('unknown');
    }

    /** @test */
    public function it_throws_exception_if_applying_transition_is_disallowed()
    {
        $this->expectException(StateException::class, 'Transition [complete] cannot be applied from current state [new] on Thinktomorrow\Trader\Unit\DummyStateMachine');

        $this->machine->apply('complete');
    }

    /** @test */
    public function it_can_apply_transition()
    {
        $dummyStatefulContract = new dummyStatefulContract();
        $machine = new DummyStateMachine($dummyStatefulContract);

        $this->assertEquals('new', $dummyStatefulContract->state());

        $machine->apply('create');
        $this->assertEquals('pending', $dummyStatefulContract->state());
    }

    /** @test */
    public function it_can_reset_same_state()
    {
        $dummyStatefulContract = new dummyStatefulContract();
        $machine = new DummyStateMachine($dummyStatefulContract);

        $this->assertEquals('new', $dummyStatefulContract->state());
        $dummyStatefulContract->changeState('new');
        $this->assertEquals('new', $dummyStatefulContract->state());
    }
}

class DummyStateMachine extends StateMachine
{
    protected $states = [
        'new',
        'pending',
        'completed',
        'canceled',
        'refunded',
    ];

    protected $transitions = [
        'create' => [
            'from' => ['new'],
            'to'   => 'pending',
        ],
        'complete' => [
            'from' => ['pending'],
            'to'   => 'completed',
        ],
    ];
}

class MalformedStateMachine extends StateMachine
{
    protected $transitions = [
        'complete' => [
            'from' => 'foobar',
        ],
    ];
}

class MissingStateMachine extends StateMachine
{
    protected $states = [
        'new',
    ];

    protected $transitions = [
        'create' => [
            'from' => ['new'],
            'to'   => 'pending',
        ],
    ];
}

class DummyStatefulContract implements StatefulContract
{
    const STATE_NEW = 'new';
    const STATE_PENDING = 'pending';

    private $currentState;

    public function __construct($state = null)
    {
        // Default state
        $this->currentState = self::STATE_NEW;
    }

    public function state(): string
    {
        return $this->currentState;
    }

    public function changeState($state)
    {
        // Validation occurs in state machine
        $this->currentState = $state;
    }
}
