<?php
declare(strict_types=1);

namespace Tests\Acceptance\Order;

use Thinktomorrow\Trader\Domain\Common\State\StateException;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderState;

final class OrderStateTest extends StateContext
{
    public function test_it_can_delete_order()
    {
        $this->assertOrderStateTransition('deleteOrder', OrderState::cart_pending, OrderState::cart_queued_for_deletion);

        // Cannot delete already confirmed order
        $this->expectException(StateException::class);
        $this->assertOrderStateTransition('deleteOrder', OrderState::confirmed, OrderState::confirmed);
    }

    public function test_it_can_confirm_order_as_business()
    {
        $this->assertCartStateTransition('confirmCartAsBusiness', OrderState::cart_completed, OrderState::confirmed_as_business);
    }

    public function test_it_can_cancel_order()
    {
        $this->assertOrderStateTransition('cancelOrder', OrderState::confirmed, OrderState::cancelled);
    }

    public function test_merchant_can_cancel_order()
    {
        $this->assertOrderStateTransition('cancelOrderByMerchant', OrderState::confirmed, OrderState::cancelled_by_merchant);
    }

    public function test_it_can_pay_confirmed_order()
    {
        $this->assertOrderStateTransition('payOrder', OrderState::confirmed, OrderState::paid);
    }

    public function test_it_cannot_pay_unconfirmed_cart()
    {
        $this->expectException(StateException::class);
        $this->assertOrderStateTransition('payOrder', OrderState::cart_pending, OrderState::paid);
    }

    public function test_it_can_partially_pay_confirmed_order()
    {
        $this->assertOrderStateTransition('partiallyPayOrder', OrderState::confirmed, OrderState::partially_paid);
    }

    public function test_it_can_mark_order_as_paid()
    {
        $this->assertOrderStateTransition('markOrderPaidByMerchant', OrderState::cart_completed, OrderState::marked_paid_by_merchant);
    }

    public function test_it_can_pack_order()
    {
        $this->assertOrderStateTransition('packOrder', OrderState::paid, OrderState::packed);
        $this->assertOrderStateTransition('packOrder', OrderState::partially_paid, OrderState::packed);
    }

    public function test_it_can_partially_pack_order()
    {
        $this->assertOrderStateTransition('partiallyPackOrder', OrderState::paid, OrderState::partially_packed);
        $this->assertOrderStateTransition('partiallyPackOrder', OrderState::partially_paid, OrderState::partially_packed);
    }

    public function test_it_can_deliver_order()
    {
        $this->assertOrderStateTransition('deliverOrder', OrderState::packed, OrderState::delivered);
        $this->assertOrderStateTransition('deliverOrder', OrderState::partially_packed, OrderState::delivered);
    }

    public function test_it_can_partially_deliver_order()
    {
        $this->assertOrderStateTransition('partiallyDeliverOrder', OrderState::packed, OrderState::partially_delivered);
        $this->assertOrderStateTransition('partiallyDeliverOrder', OrderState::partially_packed, OrderState::partially_delivered);
    }
}
