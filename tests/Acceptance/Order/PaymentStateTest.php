<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order;

use Thinktomorrow\Trader\Domain\Common\State\StateException;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;

class PaymentStateTest extends StateContext
{
    public function test_it_can_initialize_a_payment()
    {
        $this->assertPaymentStateTransition('initializePayment', PaymentState::none, PaymentState::initialized);
    }

    public function test_it_can_pay_payment_for_confirmed_order()
    {
        $this->assertPaymentStateTransition('payPayment', PaymentState::initialized, PaymentState::paid);
    }

    public function test_it_cannot_pay_payment_of_non_initialized_payment()
    {
        $this->expectException(StateException::class);
        $this->assertPaymentStateTransition('payPayment', PaymentState::none, PaymentState::paid);
    }

    public function test_merchant_can_pay_payment()
    {
        $this->assertPaymentStateTransition('payPaymentByMerchant', PaymentState::initialized, PaymentState::paid_by_merchant);
    }

    public function test_it_can_cancel_a_payment()
    {
        $this->assertPaymentStateTransition('cancelPayment',PaymentState::initialized, PaymentState::canceled);
    }

    public function test_it_can_expire_a_payment()
    {
        $this->assertPaymentStateTransition('expirePayment',PaymentState::initialized, PaymentState::expired);
    }

    public function test_it_can_refund_a_payment()
    {
        $this->assertPaymentStateTransition('refundPayment',PaymentState::paid, PaymentState::refunded);
    }

    public function test_it_can_charge_back_a_payment()
    {
        $this->assertPaymentStateTransition('chargeBackPayment',PaymentState::paid, PaymentState::charged_back);
    }
}
