<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order;

use Thinktomorrow\Trader\Domain\Common\State\StateException;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\DefaultPaymentState;

class PaymentStateTest extends StateContext
{
    public function test_it_can_initialize_a_payment()
    {
        $this->assertPaymentStateTransition('initializePayment', DefaultPaymentState::none, DefaultPaymentState::initialized);
    }

    public function test_it_can_pay_payment_for_confirmed_order()
    {
        $this->assertPaymentStateTransition('payPayment', DefaultPaymentState::initialized, DefaultPaymentState::paid);
    }

    public function test_it_cannot_pay_payment_of_non_initialized_payment()
    {
        $this->expectException(StateException::class);
        $this->assertPaymentStateTransition('payPayment', DefaultPaymentState::none, DefaultPaymentState::paid);
    }

    public function test_merchant_can_pay_payment()
    {
        $this->assertPaymentStateTransition('payPaymentByMerchant', DefaultPaymentState::initialized, DefaultPaymentState::paid_by_merchant);
    }

    public function test_it_can_cancel_a_payment()
    {
        $this->assertPaymentStateTransition('cancelPayment', DefaultPaymentState::initialized, DefaultPaymentState::canceled);
    }

    public function test_it_can_expire_a_payment()
    {
        $this->assertPaymentStateTransition('expirePayment', DefaultPaymentState::initialized, DefaultPaymentState::expired);
    }

    public function test_it_can_refund_a_payment()
    {
        $this->assertPaymentStateTransition('refundPayment', DefaultPaymentState::paid, DefaultPaymentState::refunded);
    }

    public function test_it_can_charge_back_a_payment()
    {
        $this->assertPaymentStateTransition('chargeBackPayment', DefaultPaymentState::paid, DefaultPaymentState::charged_back);
    }
}
