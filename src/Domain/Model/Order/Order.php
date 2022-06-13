<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Common\Entity\RecordsChangelog;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderCreated;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingAddress;

final class Order implements Aggregate
{
    use RecordsEvents;
    use RecordsChangelog;
    use HasTotals;
    use HasLines;
    use HasShippings;
    use HasDiscounts;
    use HasData;

    public readonly OrderId $orderId;
    private OrderState $orderState;
    private ?Shopper $shopper = null;
    private ?Payment $payment = null;
    private ?ShippingAddress $shippingAddress = null;
    private ?BillingAddress $billingAddress = null;

    private function __construct()
    {
    }

    public static function create(OrderId $orderId)
    {
        $order = new static();

        $order->orderId = $orderId;
        $order->orderState = OrderState::cart_pending;

        $order->recordEvent(new OrderCreated($order->orderId));

        return $order;
    }

    public function getOrderState(): OrderState
    {
        return $this->orderState;
    }

    public function getShippingAddress(): ?ShippingAddress
    {
        return $this->shippingAddress;
    }

    public function getShippings(): array
    {
        return $this->shippings;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
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
        $this->update('orderState', $orderState);
    }

    public function updateShopper(Shopper $shopper): void
    {
        $this->update('shopper', $shopper);
    }

    public function updatePayment(Payment $payment): void
    {
        $this->update('payment', $payment);
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


    public function getMappedData(): array
    {
        return [
            'order_id'    => $this->orderId->get(),
            'order_state' => $this->orderState->value,

            'total'          => $this->getTotal()->getMoney()->getAmount(),
            'tax_total'      => $this->getTaxTotal()->getAmount(),
            'includes_vat'   => $this->getTotal()->includesVat(),
            'subtotal'       => $this->getTotal()->includesVat()
                ? $this->getSubTotal()->getIncludingVat()->getAmount()
                : $this->getSubTotal()->getExcludingVat()->getAmount(),
            'discount_total' => $this->getTotal()->includesVat()
                ? $this->getDiscountTotal()->getIncludingVat()->getAmount()
                : $this->getDiscountTotal()->getExcludingVat()->getAmount(),
            'shipping_cost'  => $this->getTotal()->includesVat()
                ? $this->getShippingCost()->getIncludingVat()->getAmount()
                : $this->getShippingCost()->getExcludingVat()->getAmount(),
            'payment_cost'   => $this->getTotal()->includesVat()
                ? $this->getPaymentCost()->getIncludingVat()->getAmount()
                : $this->getPaymentCost()->getExcludingVat()->getAmount(),

            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            Line::class            => array_map(fn($line) => $line->getMappedData(), $this->lines),
            Discount::class        => array_map(fn($discount) => $discount->getMappedData(), $this->discounts),
            Shipping::class        => array_map(fn($shipping) => $shipping->getMappedData(), $this->shippings),
            ShippingAddress::class => $this->shippingAddress?->getMappedData(),
            BillingAddress::class  => $this->billingAddress?->getMappedData(),
            Payment::class         => $this->payment?->getMappedData(),
            Shopper::class         => $this->shopper?->getMappedData(),
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $order = new static();

        $order->orderId = OrderId::fromString($state['order_id']);
        $order->orderState = OrderState::from($state['order_state']);

        $order->lines = array_map(fn($lineState) => Line::fromMappedData($lineState, $state), $childEntities[Line::class]);
        $order->discounts = array_map(fn($discountState) => Discount::fromMappedData($discountState, $state), $childEntities[Discount::class]);
        $order->shippings = array_map(fn($shippingState) => Shipping::fromMappedData($shippingState, $state), $childEntities[Shipping::class]);
        $order->shippingAddress = $childEntities[ShippingAddress::class] ? ShippingAddress::fromMappedData($childEntities[ShippingAddress::class], $state) : null;
        $order->billingAddress = $childEntities[BillingAddress::class] ? BillingAddress::fromMappedData($childEntities[BillingAddress::class], $state) : null;
        $order->payment = $childEntities[Payment::class] ? Payment::fromMappedData($childEntities[Payment::class], $state) : null;
        $order->shopper = $childEntities[Shopper::class] ? Shopper::fromMappedData($childEntities[Shopper::class], $state) : null;

        $order->data = json_decode($state['data'], true);

        return $order;
    }
}
