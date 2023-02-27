<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order;

use Thinktomorrow\Trader\Domain\Common\State\StateException;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;

final class OrderStateTest extends StateContext
{
    public function test_it_can_delete_order()
    {
        $this->assertOrderStateTransition('deleteOrder', DefaultOrderState::cart_pending, DefaultOrderState::cart_queued_for_deletion);

        // Cannot delete already confirmed order
        $this->expectException(StateException::class);
        $this->assertOrderStateTransition('deleteOrder', DefaultOrderState::confirmed, DefaultOrderState::confirmed);
    }

    public function test_it_can_confirm_order_as_business()
    {
        $this->assertCartStateTransition('confirmCartAsBusiness', DefaultOrderState::cart_completed, DefaultOrderState::confirmed_as_business);
    }

    public function test_it_can_cancel_order()
    {
        $this->assertOrderStateTransition('cancelOrder', DefaultOrderState::confirmed, DefaultOrderState::cancelled);
    }

    public function test_merchant_can_cancel_order()
    {
        $this->assertOrderStateTransition('cancelOrderByMerchant', DefaultOrderState::confirmed, DefaultOrderState::cancelled_by_merchant);
    }

    public function test_it_can_pay_confirmed_order()
    {
        $this->assertOrderStateTransition('payOrder', DefaultOrderState::confirmed, DefaultOrderState::paid);
    }

    public function test_it_cannot_pay_unconfirmed_cart()
    {
        $this->expectException(StateException::class);
        $this->assertOrderStateTransition('payOrder', DefaultOrderState::cart_pending, DefaultOrderState::paid);
    }

    public function test_it_can_partially_pay_confirmed_order()
    {
        $this->assertOrderStateTransition('partiallyPayOrder', DefaultOrderState::confirmed, DefaultOrderState::partially_paid);
    }

    public function test_it_can_mark_order_as_paid()
    {
        $this->assertOrderStateTransition('markOrderPaidByMerchant', DefaultOrderState::cart_completed, DefaultOrderState::marked_paid_by_merchant);
    }

    public function test_it_can_pack_order()
    {
        $this->assertOrderStateTransition('packOrder', DefaultOrderState::paid, DefaultOrderState::packed);
        $this->assertOrderStateTransition('packOrder', DefaultOrderState::partially_paid, DefaultOrderState::packed);
    }

    public function test_it_can_partially_pack_order()
    {
        $this->assertOrderStateTransition('partiallyPackOrder', DefaultOrderState::paid, DefaultOrderState::partially_packed);
        $this->assertOrderStateTransition('partiallyPackOrder', DefaultOrderState::partially_paid, DefaultOrderState::partially_packed);
    }

    public function test_it_can_deliver_order()
    {
        $this->assertOrderStateTransition('deliverOrder', DefaultOrderState::packed, DefaultOrderState::delivered);
        $this->assertOrderStateTransition('deliverOrder', DefaultOrderState::partially_packed, DefaultOrderState::delivered);
    }

    public function test_it_can_partially_deliver_order()
    {
        $this->assertOrderStateTransition('partiallyDeliverOrder', DefaultOrderState::packed, DefaultOrderState::partially_delivered);
        $this->assertOrderStateTransition('partiallyDeliverOrder', DefaultOrderState::partially_packed, DefaultOrderState::partially_delivered);
    }
}
