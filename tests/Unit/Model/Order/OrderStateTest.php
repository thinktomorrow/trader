<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\CartAbandoned;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\CartCompleted;
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
use Thinktomorrow\Trader\Domain\Model\Order\Payment\DefaultPaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\DefaultShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;

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
        $this->assertOrderStateEvent(CartAbandoned::class, DefaultOrderState::cart_abandoned);
        $this->assertOrderStateEvent(CartRevived::class, DefaultOrderState::cart_revived);
        $this->assertOrderStateEvent(CartQueuedForDeletion::class, DefaultOrderState::cart_queued_for_deletion);
        $this->assertOrderStateEvent(OrderCancelled::class, DefaultOrderState::cancelled);
        $this->assertOrderStateEvent(OrderCancelledByMerchant::class, DefaultOrderState::cancelled_by_merchant);
        $this->assertOrderStateEvent(CartCompleted::class, DefaultOrderState::cart_completed);
        $this->assertOrderStateEvent(OrderConfirmed::class, DefaultOrderState::confirmed);
        $this->assertOrderStateEvent(OrderPaid::class, DefaultOrderState::paid);
        $this->assertOrderStateEvent(OrderPartiallyPaid::class, DefaultOrderState::partially_paid);
        $this->assertOrderStateEvent(OrderPacked::class, DefaultOrderState::packed);
        $this->assertOrderStateEvent(OrderPartiallyPacked::class, DefaultOrderState::partially_packed);
        $this->assertOrderStateEvent(OrderDelivered::class, DefaultOrderState::delivered);
        $this->assertOrderStateEvent(OrderPartiallyDelivered::class, DefaultOrderState::partially_delivered);
    }

    /** @test */
    public function it_can_record_payment_events()
    {
        $this->assertPaymentStateEvent(PaymentPaid::class, DefaultPaymentState::paid);
        $this->assertPaymentStateEvent(PaymentMarkedPaidByMerchant::class, DefaultPaymentState::paid_by_merchant);
        $this->assertPaymentStateEvent(PaymentFailed::class, DefaultPaymentState::failed);
        $this->assertPaymentStateEvent(PaymentFailed::class, DefaultPaymentState::canceled);
        $this->assertPaymentStateEvent(PaymentFailed::class, DefaultPaymentState::expired);
        $this->assertPaymentStateEvent(PaymentRefunded::class, DefaultPaymentState::refunded);
        $this->assertPaymentStateEvent(PaymentRefunded::class, DefaultPaymentState::charged_back);
        $this->assertPaymentStateEvent(PaymentRefundedByMerchant::class, DefaultPaymentState::refunded_by_merchant);
    }

    /** @test */
    public function it_can_record_shipping_events()
    {
        $this->assertShippingStateEvent(ShipmentMarkedReadyForPacking::class, DefaultShippingState::ready_for_packing);
        $this->assertShippingStateEvent(ShipmentHaltedForPacking::class, DefaultShippingState::halted_for_packing);
        $this->assertShippingStateEvent(ShipmentPacked::class, DefaultShippingState::packed);
        $this->assertShippingStateEvent(ShipmentInTransit::class, DefaultShippingState::in_transit);
        $this->assertShippingStateEvent(ShipmentDelivered::class, DefaultShippingState::delivered);
        $this->assertShippingStateEvent(ShipmentReturned::class, DefaultShippingState::returned);
        $this->assertShippingStateEvent(ShipmentFailed::class, DefaultShippingState::failed);
    }

    public function test_does_not_record_event_when_state_hasnt_changed()
    {
        $this->assertOrderStateEvent(CartAbandoned::class, DefaultOrderState::cart_abandoned);
        $this->assertNoOrderStateEvent(CartAbandoned::class, DefaultOrderState::cart_abandoned);
    }

    /** @test */
    public function it_does_not_record_payment_event_when_state_hasnt_changed()
    {
        $this->assertNoPaymentStateEvent(PaymentInitialized::class, DefaultPaymentState::initialized);

        $this->assertPaymentStateEvent(PaymentPaid::class, DefaultPaymentState::paid);
        $this->assertNoPaymentStateEvent(PaymentPaid::class, DefaultPaymentState::paid);
    }

    /** @test */
    public function it_does_not_record_shipping_events_when_state_hasnt_changed()
    {
        $this->assertShippingStateEvent(ShipmentMarkedReadyForPacking::class, DefaultShippingState::ready_for_packing);
        $this->assertNoShippingStateEvent(ShipmentMarkedReadyForPacking::class, DefaultShippingState::ready_for_packing);
        $this->assertShippingStateEvent(ShipmentPacked::class, DefaultShippingState::packed);
        $this->assertNoShippingStateEvent(ShipmentPacked::class, DefaultShippingState::packed);
    }

    private function assertOrderStateEvent(string $eventClass, $newState, $oldState = null)
    {
        $oldState = $oldState ?: $this->order->getOrderState();

        $this->order->updateState($newState, ['foo' => 'bar']);

        $this->assertEquals([
            new OrderUpdated($this->order->orderId),
            new $eventClass($this->order->orderId, $oldState, $newState, ['foo' => 'bar']),
            new OrderStateUpdated($this->order->orderId, $oldState, $newState),
        ], $this->order->releaseEvents());
    }

    private function assertNoOrderStateEvent(string $eventClass, $newState, $oldState = null)
    {
        $oldState = $oldState ?: $this->order->getOrderState();

        $this->order->updateState($newState, ['foo' => 'bar']);

        $this->assertEquals([], $this->order->releaseEvents());
    }

    private function assertNoPaymentStateEvent(string $eventClass, $newState, $oldState = null)
    {
        $payment = $this->order->getPayments()[0];

        $oldState = $oldState ?: $payment->getPaymentState();

        $this->order->updatePaymentState($payment->paymentId, $newState, ['foo' => 'bar']);

        $this->assertEquals([], $this->order->releaseEvents());
    }

    private function assertPaymentStateEvent(string $eventClass, $newState, $oldState = null)
    {
        $payment = $this->order->getPayments()[0];

        $oldState = $oldState ?: $payment->getPaymentState();

        $this->order->updatePaymentState($payment->paymentId, $newState, ['foo' => 'bar']);

        $this->assertEquals([
            new $eventClass($this->order->orderId, $payment->paymentId, $oldState, $newState, ['foo' => 'bar']),
            new PaymentStateUpdated($this->order->orderId, $payment->paymentId, $oldState, $newState),
            new OrderUpdated($this->order->orderId),
        ], $this->order->releaseEvents());
    }

    private function assertShippingStateEvent(string $eventClass, $newState, $oldState = null)
    {
        $shipping = $this->order->getShippings()[0];

        $oldState = $oldState ?: $shipping->getShippingState();

        $this->order->updateShippingState($shipping->shippingId, $newState, ['foo' => 'bar']);

        $this->assertEquals([
            new $eventClass($this->order->orderId, $shipping->shippingId, $oldState, $newState, ['foo' => 'bar']),
            new ShippingStateUpdated($this->order->orderId, $shipping->shippingId, $oldState, $newState),
            new OrderUpdated($this->order->orderId),
        ], $this->order->releaseEvents());
    }

    private function assertNoShippingStateEvent(string $eventClass, $newState, $oldState = null)
    {
        $shipping = $this->order->getShippings()[0];

        $oldState = $oldState ?: $shipping->getShippingState();

        $this->order->updateShippingState($shipping->shippingId, $newState, ['foo' => 'bar']);

        $this->assertEquals([], $this->order->releaseEvents());
    }
}
