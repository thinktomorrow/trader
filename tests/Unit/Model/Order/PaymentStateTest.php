<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentStateMachine;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\MerchantOrder\DefaultMerchantOrderPayment;

class PaymentStateTest extends TestCase
{
    private PaymentStateMachine $machine;
    private \Thinktomorrow\Trader\Domain\Model\Order\Order $order;
    private \Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->machine = new PaymentStateMachine([
            PaymentState::none,
            PaymentState::initialized,
            PaymentState::paid,
        ], [
            'initialize' => [
                'from' => [PaymentState::none],
                'to' => PaymentState::initialized,
            ],
            'pay' => [
                'from' => [PaymentState::initialized],
                'to' => PaymentState::paid,
            ],
        ]);

        $this->order = $this->createDefaultOrder();
        $this->payment = $this->order->getPayments()[0];

        $this->assertEquals(PaymentState::initialized, $this->payment->getPaymentState());

        $this->machine->setOrder($this->order);
    }

    public function test_it_can_create_state_machine()
    {
        $this->assertTrue($this->machine->can($this->payment, 'pay'));
        $this->assertFalse($this->machine->can($this->payment, 'initialize'));

        $this->machine->apply($this->payment, 'pay');

        $this->assertEquals(PaymentState::paid, $this->payment->getPaymentState());
    }

    public function test_it_can_create_state_machine_for_merchant_order()
    {
        $merchantOrderPayment = DefaultMerchantOrderPayment::fromMappedData(array_merge($this->payment->getMappedData(), [
            'cost' => $this->payment->getPaymentCost(),
        ]), $this->order->getMappedData(), []);
        $this->assertTrue($this->machine->can($merchantOrderPayment, 'pay'));
        $this->assertFalse($this->machine->can($merchantOrderPayment, 'initialize'));

        $this->machine->apply($this->payment, 'pay');
        $this->assertEquals(PaymentState::paid, $this->payment->getPaymentState());
    }

    public function test_it_can_create_machine_with_default_transitions()
    {
        $machine = new PaymentStateMachine(PaymentState::cases(), PaymentState::getDefaultTransitions());
        $machine->setOrder($this->order);

        $machine->apply($this->payment, 'pay');
        $this->assertEquals(PaymentState::paid, $this->payment->getPaymentState());
    }
}
