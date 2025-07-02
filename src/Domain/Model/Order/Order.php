<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discountable;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableId;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\HasDiscounts;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderBillingAddressDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderBillingAddressUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderCreated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderShippingAddressDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderShippingAddressUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderStateUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\PaymentStateUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStateUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceReference;
use Thinktomorrow\Trader\Domain\Model\Order\Line\HasLines;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisation;
use Thinktomorrow\Trader\Domain\Model\Order\OrderEvent\HasOrderEvents;
use Thinktomorrow\Trader\Domain\Model\Order\OrderEvent\OrderEvent;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\HasPayments;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentState;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\HasShippings;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderState;

final class Order implements Aggregate, Discountable
{
    use RecordsEvents;
    use HasTotals;
    use HasLines;
    use HasShippings;
    use HasPayments;
    use HasDiscounts;
    use HasOrderEvents;
    use HasData;

    public readonly OrderId $orderId;
    private OrderState $orderState;
    public readonly OrderReference $orderReference;
    private ?InvoiceReference $invoiceReference = null;

    private ?ShippingAddress $shippingAddress = null;
    private ?BillingAddress $billingAddress = null;
    private ?Shopper $shopper = null;

    private function __construct()
    {
    }

    public static function create(OrderId $orderId, OrderReference $orderReference, OrderState $orderState)
    {
        $order = new static();

        $order->orderId = $orderId;
        $order->orderReference = $orderReference;
        $order->orderState = $orderState;

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

    public function updateShopper(Shopper $shopper): void
    {
        $this->update('shopper', $shopper);
    }

    public function deleteShopper(): void
    {
        $this->update('shopper', null);
    }

    public function updateState(OrderState $orderState, array $data = []): void
    {
        $oldState = $this->getOrderState();

        if ($oldState->equals($orderState)) {
            return;
        }

        $this->update('orderState', $orderState);

        $this->recordOrderStateEvent($oldState, $orderState, $data);
        $this->recordEvent(new OrderStateUpdated($this->orderId, $oldState, $orderState));
    }

    private function recordOrderStateEvent(OrderState $oldState, OrderState $newState, array $data): void
    {
        $map = $newState::getEventMapping();

        if (isset($map[$newState->value])) {
            $this->recordEvent(new $map[$newState->value]($this->orderId, $oldState, $newState, $data));
        }
    }

    public function updatePaymentState(PaymentId $paymentId, PaymentState $paymentState, array $data = []): void
    {
        $oldPaymentState = $this->findPayment($paymentId)->getPaymentState();

        if ($oldPaymentState->equals($paymentState)) {
            return;
        }

        $this->findPayment($paymentId)->updateState($paymentState);

        $this->recordPaymentStateEvent($paymentId, $oldPaymentState, $paymentState, $data);
        $this->recordEvent(new PaymentStateUpdated($this->orderId, $paymentId, $oldPaymentState, $paymentState));
        $this->recordEvent(new OrderUpdated($this->orderId));
    }

    private function recordPaymentStateEvent(PaymentId $paymentId, PaymentState $oldState, PaymentState $newState, array $data): void
    {
        $map = $newState::getEventMapping();

        if (isset($map[$newState->value])) {
            $this->recordEvent(new $map[$newState->value]($this->orderId, $paymentId, $oldState, $newState, $data));
        }
    }

    public function updateShippingState(ShippingId $shippingId, ShippingState $shippingState, array $data = []): void
    {
        $oldShippingState = $this->findShipping($shippingId)->getShippingState();

        if ($oldShippingState->equals($shippingState)) {
            return;
        }

        $this->findShipping($shippingId)->updateState($shippingState);

        $this->recordShippingStateEvent($shippingId, $oldShippingState, $shippingState, $data);
        $this->recordEvent(new ShippingStateUpdated($this->orderId, $shippingId, $oldShippingState, $shippingState));
        $this->recordEvent(new OrderUpdated($this->orderId));
    }

    private function recordShippingStateEvent(ShippingId $shippingId, ShippingState $oldState, ShippingState $newState, array $data): void
    {
        $map = $newState::getEventMapping();

        if (isset($map[$newState->value])) {
            $this->recordEvent(new $map[$newState->value]($this->orderId, $shippingId, $oldState, $newState, $data));
        }
    }

    public function updateShippingAddress(ShippingAddress $shippingAddress): void
    {
        $this->update('shippingAddress', $shippingAddress);

        $this->recordEvent(new OrderShippingAddressUpdated($this->orderId));
    }

    public function deleteShippingAddress(): void
    {
        $this->update('shippingAddress', null);

        $this->recordEvent(new OrderShippingAddressDeleted($this->orderId));
    }

    public function updateBillingAddress(BillingAddress $billingAddress): void
    {
        $this->update('billingAddress', $billingAddress);

        $this->recordEvent(new OrderBillingAddressUpdated($this->orderId));
    }

    public function deleteBillingAddress(): void
    {
        $this->update('billingAddress', null);

        $this->recordEvent(new OrderBillingAddressDeleted($this->orderId));
    }

    public function setInvoiceReference(InvoiceReference $invoiceReference): void
    {
        $this->invoiceReference = $invoiceReference;
    }

    public function getInvoiceReference(): ?InvoiceReference
    {
        return $this->invoiceReference;
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

    public function isVatExempt(): bool
    {
        return $this->getData('is_vat_exempt') ?? false;
    }

    public function setVatExempt(bool $is_vat_exempt): void
    {
        $this->addData(['is_vat_exempt' => $is_vat_exempt]);
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
            'invoice_ref' => $this->invoiceReference?->get(),
            'order_state' => $this->orderState->getValueAsString(),

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
            OrderEvent::class => array_map(fn ($orderEvent) => $orderEvent->getMappedData(), $this->orderEvents),
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $order = new static();

        if (! $state['order_state'] instanceof OrderState) {
            throw new \InvalidArgumentException('Order state is expected to be instance of OrderState. Instead ' . gettype($state['order_state']) . ' is passed.');
        }

        $order->orderId = OrderId::fromString($state['order_id']);
        $order->orderReference = OrderReference::fromString($state['order_ref']);
        $order->invoiceReference = $state['invoice_ref'] ? InvoiceReference::fromString($state['invoice_ref']) : null;
        $order->orderState = $state['order_state'];
        $order->discounts = array_map(fn ($discountState) => Discount::fromMappedData($discountState, $state), $childEntities[Discount::class]);

        $order->lines = array_map(fn ($lineState) => Line::fromMappedData($lineState, $state, [
            Discount::class => $lineState[Discount::class],
            LinePersonalisation::class => $lineState[LinePersonalisation::class],
        ]), $childEntities[Line::class]);
        $order->shippings = array_map(fn ($shippingState) => Shipping::fromMappedData($shippingState, $state, [Discount::class => $shippingState[Discount::class]]), $childEntities[Shipping::class]);
        $order->payments = array_map(fn ($paymentState) => Payment::fromMappedData($paymentState, $state, [Discount::class => $paymentState[Discount::class]]), $childEntities[Payment::class]);
        $order->shippingAddress = $childEntities[ShippingAddress::class] ? ShippingAddress::fromMappedData($childEntities[ShippingAddress::class], $state) : null;
        $order->billingAddress = $childEntities[BillingAddress::class] ? BillingAddress::fromMappedData($childEntities[BillingAddress::class], $state) : null;
        $order->shopper = $childEntities[Shopper::class] ? Shopper::fromMappedData($childEntities[Shopper::class], $state) : null;

        $order->data = json_decode($state['data'], true);
        $order->orderEvents = array_map(fn ($orderEventState) => OrderEvent::fromMappedData($orderEventState, $state), $childEntities[OrderEvent::class]);

        return $order;
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
