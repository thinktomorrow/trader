<?php

namespace Thinktomorrow\Trader\Unit;

use Thinktomorrow\Trader\Common\Domain\State\StateException;
use Thinktomorrow\Trader\Orders\Domain\MerchantOrderState;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Orders\Domain\OrderState;
use Thinktomorrow\Trader\Orders\Ports\Read\MerchantOrder;
use Thinktomorrow\Trader\Tests\UnitTestCase;

class MerchantOrderStateMachineTest extends UnitTestCase
{
    private $merchantOrder;
    private $machine;

    public function setUp()
    {
        parent::setUp();

        $this->merchantOrder = new MerchantOrder(['state' => 'paid']);
        $this->machine = new MerchantOrderState($this->merchantOrder);
    }

    /** @test */
    public function it_can_apply_transition()
    {
        $this->assertEquals('paid', $this->merchantOrder->state());

        $this->machine->apply('queue');
        $this->assertEquals(MerchantOrderState::READY_FOR_PROCESS, $this->merchantOrder->state());
    }
}
