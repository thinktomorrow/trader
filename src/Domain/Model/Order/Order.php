<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Common\Price\Price;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Price\PriceTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Common\Entity\RecordsChangelog;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderCreated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discountable;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableId;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStateUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStateUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStateUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPaid;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\CartRevived;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPacked;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\CartAbandoned;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentPaid;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderCancelled;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderConfirmed;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderDelivered;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderFulfilled;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentFailed;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentRefunded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentPacked;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShippingFailed;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPartiallyPaid;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentReturned;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPartiallyPacked;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentInitialized;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentDelivered;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentInTransit;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\CartQueuedForDeletion;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderMarkedUnfulfilled;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderPartiallyDelivered;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStates\OrderCancelledByMerchant;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentHaltedForPacking;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStates\PaymentMarkedPaidByMerchant;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentMarkedReadyForPacking;

final class Order implements Aggregate, Discountable
{
    use RecordsEvents;
    use RecordsChangelog;
    use HasTotals;
    use HasLines;
    use HasShippings;
    use HasPayments;
    use HasDiscounts;
    use HasData;

    public readonly OrderId $orderId;
    public readonly OrderReference $orderReference;
    private OrderState $orderState;
    private ?ShippingAddress $shippingAddress = null;
    private ?BillingAddress $billingAddress = null;
    private ?Shopper $shopper = null;

    private function __construct()
    {
    }

    public static function create(OrderId $orderId, OrderReference $orderReference)
    {
        $order = new static();

        $order->orderId = $orderId;
        $order->orderReference = $orderReference;
        $order->orderState = OrderState::cart_pending;

        $order->recordEvent(new OrderCreated($order->orderId));

        return $order;
    }

    public function getOrderState(): OrderState
    {
        return $this->orderState;
    }

    public function inCustomerHands(): bool
    {
        return $this->getOrderState()->inCustomerHands();
    }

    public function getShippingAddress(): ?ShippingAddress
    {
        return $this->shippingAddress;
    }

    /** @return Shipping[] */
    public function getShippings(): array
    {
        return $this->shippings;
    }

    /** @return Payment[] */
    public function getPayments(): array
    {
        return $this->payments;
    }

    public function getBillingAddress(): ?BillingAddress
    {
        return $this->billingAddress;
    }

    public function getShopper(): ?Shopper
    {
        return $this->shopper;
    }

    public function updateState(OrderState $orderState): void
    {
        $oldState = $this->getOrderState();

        $this->update('orderState', $orderState);

        $this->recordOrderStateEvent($oldState, $orderState);
        $this->recordEvent(new OrderStateUpdated($this->orderId, $oldState, $orderState));
    }

    private function recordOrderStateEvent(OrderState $oldState, OrderState $newState): void
    {
        $map = [
            OrderState::cart_abandoned->value => CartAbandoned::class,
            OrderState::cart_revived->value => CartRevived::class,
            OrderState::cart_queued_for_deletion->value => CartQueuedForDeletion::class,
            OrderState::confirmed->value => OrderConfirmed::class,
            OrderState::cancelled->value => OrderCancelled::class,
            OrderState::cancelled_by_merchant->value => OrderCancelledByMerchant::class,
            OrderState::paid->value => OrderPaid::class,
            OrderState::partially_paid->value => OrderPartiallyPaid::class,
            OrderState::packed->value => OrderPacked::class,
            OrderState::partially_packed->value => OrderPartiallyPacked::class,
            OrderState::delivered->value => OrderDelivered::class,
            OrderState::partially_delivered->value => OrderPartiallyDelivered::class,
            OrderState::fulfilled->value => OrderFulfilled::class,
            OrderState::unfulfilled->value => OrderMarkedUnfulfilled::class,
        ];

        if (isset($map[$newState->value])) {
            $this->recordEvent(new $map[$newState->value]($this->orderId, $oldState, $newState));
        }
    }

    public function updateShopper(Shopper $shopper): void
    {
        $this->update('shopper', $shopper);
    }

    public function updatePaymentState(PaymentId $paymentId, PaymentState $paymentState): void
    {
        $oldPaymentState = $this->findPayment($paymentId)->getPaymentState();

        $this->findPayment($paymentId)->updateState($paymentState);

        $this->recordPaymentStateEvent($paymentId, $oldPaymentState, $paymentState);
        $this->recordEvent(new PaymentStateUpdated($this->orderId, $paymentId, $oldPaymentState, $paymentState));
        $this->recordEvent(new OrderUpdated($this->orderId));
    }

    private function recordPaymentStateEvent(PaymentId $paymentId, PaymentState $oldState, PaymentState $newState): void
    {
        $map = [
            PaymentState::initialized->value => PaymentInitialized::class,
            PaymentState::paid->value => PaymentPaid::class,
            PaymentState::paid_by_merchant->value => PaymentMarkedPaidByMerchant::class,
            PaymentState::canceled->value => PaymentFailed::class,
            PaymentState::failed->value => PaymentFailed::class,
            PaymentState::expired->value => PaymentFailed::class,
            PaymentState::refunded->value => PaymentRefunded::class,
            PaymentState::charged_back->value => PaymentRefunded::class,
        ];

        if (isset($map[$newState->value])) {
            $this->recordEvent(new $map[$newState->value]($this->orderId, $paymentId, $oldState, $newState));
        }
    }

    public function updateShippingState(ShippingId $shippingId, ShippingState $shippingState): void
    {
        $oldShippingState = $this->findShipping($shippingId)->getShippingState();

        $this->findShipping($shippingId)->updateState($shippingState);

        $this->recordShippingStateEvent($shippingId, $oldShippingState, $shippingState);
        $this->recordEvent(new ShippingStateUpdated($this->orderId, $shippingId, $oldShippingState, $shippingState));
        $this->recordEvent(new OrderUpdated($this->orderId));
    }

    private function recordShippingStateEvent(ShippingId $shippingId, ShippingState $oldState, ShippingState $newState): void
    {
        $map = [
            ShippingState::ready_for_packing->value => ShipmentMarkedReadyForPacking::class,
            ShippingState::halted_for_packing->value => ShipmentHaltedForPacking::class,
            ShippingState::packed->value => ShipmentPacked::class,
            ShippingState::in_transit->value => ShipmentInTransit::class,
            ShippingState::delivered->value => ShipmentDelivered::class,
            ShippingState::returned->value => ShipmentReturned::class,
            ShippingState::failed->value => ShippingFailed::class,
        ];

        if (isset($map[$newState->value])) {
            $this->recordEvent(new $map[$newState->value]($this->orderId, $shippingId, $oldState, $newState));
        }
    }

    public function updateShippingAddress(ShippingAddress $shippingAddress): void
    {
        $this->update('shippingAddress', $shippingAddress);
    }

    public function updateBillingAddress(BillingAddress $billingAddress): void
    {
        $this->update('billingAddress', $billingAddress);
    }

    // addresses, methods, state,
    private function update($property, $value): void
    {
        $this->{$property} = $value;

        // How to bump version - how to know we are in current (already bumped) version
        // + add to version notes???
        $this->recordEvent(new OrderUpdated($this->orderId));
    }

    public function getEnteredCouponCode(): ?string
    {
        return $this->getData('coupon_code');
    }

    public function setEnteredCouponCode(string $coupon_code): void
    {
        $this->addData(['coupon_code' => $coupon_code]);
    }

    public function removeEnteredCouponCode(): void
    {
        $this->deleteData('coupon_code');
    }

    public function getMappedData(): array
    {
        return [
            'order_id' => $this->orderId->get(),
            'order_ref' => $this->orderReference->get(),
            'order_state' => $this->orderState->value,

            'total' => $this->getTotal()->getMoney()->getAmount(),
            'tax_total' => $this->getTaxTotal()->getAmount(),
            'includes_vat' => $this->getTotal()->includesVat(),
            'subtotal' => $this->getTotal()->includesVat()
                ? $this->getSubTotal()->getIncludingVat()->getAmount()
                : $this->getSubTotal()->getExcludingVat()->getAmount(),
            'discount_total' => $this->getTotal()->includesVat()
                ? $this->getDiscountTotal()->getIncludingVat()->getAmount()
                : $this->getDiscountTotal()->getExcludingVat()->getAmount(),
            'shipping_cost' => $this->getTotal()->includesVat()
                ? $this->getShippingCost()->getIncludingVat()->getAmount()
                : $this->getShippingCost()->getExcludingVat()->getAmount(),
            'payment_cost' => $this->getTotal()->includesVat()
                ? $this->getPaymentCost()->getIncludingVat()->getAmount()
                : $this->getPaymentCost()->getExcludingVat()->getAmount(),

            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            Line::class => array_map(fn ($line) => $line->getMappedData(), $this->lines),
            Discount::class => array_map(fn ($discount) => $discount->getMappedData(), $this->discounts),
            Shipping::class => array_map(fn ($shipping) => $shipping->getMappedData(), $this->shippings),
            Payment::class => array_map(fn ($payment) => $payment->getMappedData(), $this->payments),
            ShippingAddress::class => $this->shippingAddress?->getMappedData(),
            BillingAddress::class => $this->billingAddress?->getMappedData(),
            Shopper::class => $this->shopper?->getMappedData(),
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $order = new static();

        $order->orderId = OrderId::fromString($state['order_id']);
        $order->orderReference = OrderReference::fromString($state['order_ref']);
        $order->orderState = OrderState::from($state['order_state']);

        $order->discounts = array_map(fn ($discountState) => Discount::fromMappedData($discountState, $state), $childEntities[Discount::class]);
        $order->lines = array_map(fn ($lineState) => Line::fromMappedData($lineState, $state, [Discount::class => $lineState[Discount::class]]), $childEntities[Line::class]);
        $order->shippings = array_map(fn ($shippingState) => Shipping::fromMappedData($shippingState, $state, [Discount::class => $shippingState[Discount::class]]), $childEntities[Shipping::class]);
        $order->payments = array_map(fn ($paymentState) => Payment::fromMappedData($paymentState, $state, [Discount::class => $paymentState[Discount::class]]), $childEntities[Payment::class]);
        $order->shippingAddress = $childEntities[ShippingAddress::class] ? ShippingAddress::fromMappedData($childEntities[ShippingAddress::class], $state) : null;
        $order->billingAddress = $childEntities[BillingAddress::class] ? BillingAddress::fromMappedData($childEntities[BillingAddress::class], $state) : null;
        $order->shopper = $childEntities[Shopper::class] ? Shopper::fromMappedData($childEntities[Shopper::class], $state) : null;

        $order->data = json_decode($state['data'], true);

        return $order;
    }

    public function getDiscountableTotal(array $conditions): Price|PriceTotal
    {
        return $this->getSubTotal();
    }

    public function getDiscountableQuantity(array $conditions): Quantity
    {
        return $this->getQuantity();
    }

    public function getDiscountableId(): DiscountableId
    {
        return DiscountableId::fromString($this->orderId->get());
    }

    public function getDiscountableType(): DiscountableType
    {
        return DiscountableType::order;
    }
}
