<?php

namespace Thinktomorrow\Trader\Tests\Common\States;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Common\State\StateException;
use Thinktomorrow\Trader\Common\State\Stateful;
use Thinktomorrow\Trader\Common\State\AbstractStateMachine;

class StateMachineTest extends TestCase
{
    private AbstractStateMachine $onlineStateMachine;

    private Stateful $statefulStub;

    public function setUp(): void
    {
        parent::setUp();

        $this->statefulStub = new StatefulStub();
        $this->onlineStateMachine = new OnlineAbstractStateMachineStub($this->statefulStub);
    }

    /** @test */
    public function it_can_apply_transition()
    {
        $this->assertSame('offline', $this->statefulStub->getState(StatefulStub::ONLINE_STATEKEY));

        $this->onlineStateMachine->apply('publish');
        $this->assertEquals('online', $this->statefulStub->getState(StatefulStub::ONLINE_STATEKEY));
        $this->assertEquals('online', $this->onlineStateMachine->getState());
    }

    /** @test */
    public function it_cannot_change_to_invalid_state()
    {
        $this->expectException(StateException::class);

        $this->onlineStateMachine->apply('foobar');
    }

    /** @test */
    public function it_only_allows_transition_to_allowed_state()
    {
        $this->expectException(StateException::class);

        $this->onlineStateMachine->apply('unpublish');
    }

    /** @test */
    public function it_can_emit_event_after_transition()
    {
        $this->assertFalse($this->onlineStateMachine->fakePublishedEventEmitted);

        $this->onlineStateMachine->emitEvent('publish');

        $this->assertTrue($this->onlineStateMachine->fakePublishedEventEmitted);
    }
}
