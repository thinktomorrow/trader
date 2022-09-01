<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\CartAbandoned;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\CartQueuedForDeletion;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\CartRevived;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderCancelled;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderCancelledByMerchant;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderConfirmed;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderDelivered;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPacked;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPaid;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPartiallyDelivered;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPartiallyPacked;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPartiallyPaid;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStateUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentFailed;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentInitialized;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentMarkedPaidByMerchant;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentPaid;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentRefunded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentRefundedByMerchant;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStateUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentDelivered;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentFailed;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentHaltedForPacking;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentInTransit;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentMarkedReadyForPacking;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentPacked;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentReturned;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStateUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderState;

class OrderStateTest extends TestCase
{
    private \Thinktomorrow\Trader\Domain\Model\Order\Order $order;

    protected function setUp(): void
    {
        parent::setUp();


        $this->order = $this->createDefaultOrder();
    }

    /** @test */
    public function it_can_record_order_events()
    {
        $this->assertOrderStateEvent(CartAbandoned::class, OrderState::cart_abandoned);
        $this->assertOrderStateEvent(CartRevived::class, OrderState::cart_revived);
        $this->assertOrderStateEvent(CartQueuedForDeletion::class, OrderState::cart_queued_for_deletion);
        $this->assertOrderStateEvent(OrderCancelled::class, OrderState::cancelled);
        $this->assertOrderStateEvent(OrderCancelledByMerchant::class, OrderState::cancelled_by_merchant);
        $this->assertOrderStateEvent(OrderConfirmed::class, OrderState::confirmed);
        $this->assertOrderStateEvent(OrderPaid::class, OrderState::paid);
        $this->assertOrderStateEvent(OrderPartiallyPaid::class, OrderState::partially_paid);
        $this->assertOrderStateEvent(OrderPacked::class, OrderState::packed);
        $this->assertOrderStateEvent(OrderPartiallyPacked::class, OrderState::partially_packed);
        $this->assertOrderStateEvent(OrderDelivered::class, OrderState::delivered);
        $this->assertOrderStateEvent(OrderPartiallyDelivered::class, OrderState::partially_delivered);
    }

    /** @test */
    public function it_can_record_payment_events()
    {
        $this->assertPaymentStateEvent(PaymentInitialized::class, PaymentState::initialized);
        $this->assertPaymentStateEvent(PaymentPaid::class, PaymentState::paid);
        $this->assertPaymentStateEvent(PaymentMarkedPaidByMerchant::class, PaymentState::paid_by_merchant);
        $this->assertPaymentStateEvent(PaymentFailed::class, PaymentState::failed);
        $this->assertPaymentStateEvent(PaymentFailed::class, PaymentState::canceled);
        $this->assertPaymentStateEvent(PaymentFailed::class, PaymentState::expired);
        $this->assertPaymentStateEvent(PaymentRefunded::class, PaymentState::refunded);
        $this->assertPaymentStateEvent(PaymentRefunded::class, PaymentState::charged_back);
        $this->assertPaymentStateEvent(PaymentRefundedByMerchant::class, PaymentState::refunded_by_merchant);
    }

    /** @test */
    public function it_can_record_shipping_events()
    {
        $this->assertShippingStateEvent(ShipmentMarkedReadyForPacking::class, ShippingState::ready_for_packing);
        $this->assertShippingStateEvent(ShipmentHaltedForPacking::class, ShippingState::halted_for_packing);
        $this->assertShippingStateEvent(ShipmentPacked::class, ShippingState::packed);
        $this->assertShippingStateEvent(ShipmentInTransit::class, ShippingState::in_transit);
        $this->assertShippingStateEvent(ShipmentDelivered::class, ShippingState::delivered);
        $this->assertShippingStateEvent(ShipmentReturned::class, ShippingState::returned);
        $this->assertShippingStateEvent(ShipmentFailed::class, ShippingState::failed);
    }

    private function assertOrderStateEvent(string $eventClass, $newState, $oldState = null)
    {
        $oldState = $oldState ?: $this->order->getOrderState();

        $this->order->updateState($newState);

        $this->assertEquals([
            new OrderUpdated($this->order->orderId),
            new $eventClass($this->order->orderId, $oldState, $newState),
            new OrderStateUpdated($this->order->orderId, $oldState, $newState),
        ], $this->order->releaseEvents());
    }

    private function assertPaymentStateEvent(string $eventClass, $newState, $oldState = null)
    {
        $payment = $this->order->getPayments()[0];

        $oldState = $oldState ?: $payment->getPaymentState();

        $this->order->updatePaymentState($payment->paymentId, $newState);

        $this->assertEquals([
            new $eventClass($this->order->orderId, $payment->paymentId, $oldState, $newState),
            new PaymentStateUpdated($this->order->orderId, $payment->paymentId, $oldState, $newState),
            new OrderUpdated($this->order->orderId),
        ], $this->order->releaseEvents());
    }

    private function assertShippingStateEvent(string $eventClass, $newState, $oldState = null)
    {
        $shipping = $this->order->getShippings()[0];

        $oldState = $oldState ?: $shipping->getShippingState();

        $this->order->updateShippingState($shipping->shippingId, $newState);

        $this->assertEquals([
            new $eventClass($this->order->orderId, $shipping->shippingId, $oldState, $newState),
            new ShippingStateUpdated($this->order->orderId, $shipping->shippingId, $oldState, $newState),
            new OrderUpdated($this->order->orderId),
        ], $this->order->releaseEvents());
    }
}
