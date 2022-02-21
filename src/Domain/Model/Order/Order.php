<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Model\Order\Price\Total;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\Order\Price\SubTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LinePrice;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineDeleted;
use Thinktomorrow\Trader\Domain\Common\Entity\RecordsChangelog;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderCreated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\ShippingAlreadyOnOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindShippingOnOrder;

final class Order implements Aggregate
{
    use RecordsEvents;
    use RecordsChangelog;
    use HasData;

    public readonly OrderId $orderId;
    private array $lines = [];
    private array $discounts = [];
    private array $shippings = [];
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

        $order->recordEvent(new OrderCreated($order->orderId));

        return $order;
    }

    public function getSubTotal(): SubTotal
    {
        if (count($this->lines) < 1) {
            return SubTotal::zero();
        }

        $price = array_reduce($this->lines, function (?Price $carry, Line $line) {
            return $carry === null
                ? $line->getTotal()
                : $carry->add($line->getTotal());
        }, null);

        return SubTotal::fromPrice($price);
    }

    public function getTotal(): Total
    {
        return Total::fromPrice($this->getSubTotal())
            ->subtract($this->getDiscountTotal())
            ->add($this->getShippingCost())
            ->add($this->getPaymentCost());
    }

    public function getDiscountTotal(): DiscountTotal
    {
        if (count($this->discounts) < 1) {
            return DiscountTotal::zero();
        }

        return array_reduce($this->discounts, function (?Price $carry, Discount $discount) {
            return $carry === null
                ? $discount->getTotal()
                : $carry->add($discount->getTotal());
        }, null);
    }

    public function getShippingCost(): ShippingCost
    {
        if (count($this->shippings) < 1) {
            return ShippingCost::zero();
        }

        return array_reduce($this->shippings, function (?Price $carry, Shipping $shipping) {
            return $carry === null
                ? $shipping->getShippingCost()
                : $carry->add($shipping->getShippingCost());
        }, null);
    }

    public function getPaymentCost(): PaymentCost
    {
        return $this->payment ? $this->payment->getPaymentCost() : PaymentCost::zero();
    }

    public function getShippingAddress(): ShippingAddress
    {
        return $this->shippingAddress ?? ShippingAddress::empty();
    }

    public function getShippings(): array
    {
        return $this->shippings;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function getBillingAddress(): BillingAddress
    {
        return $this->billingAddress ?? BillingAddress::empty();
    }

    public function getShopper(): ?Shopper
    {
        return $this->shopper;
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

    public function addOrUpdateLine(LineId $lineId, VariantId $productId, LinePrice $linePrice, Quantity $quantity): void
    {
        if (null !== $this->findLineIndex($lineId)) {
            $this->updateLine($lineId, $linePrice, $quantity);

            return;
        }

        $this->addLine($lineId, $productId, $linePrice, $quantity);
    }

    private function addLine(LineId $lineId, VariantId $productId, LinePrice $linePrice, Quantity $quantity): void
    {
        $this->lines[] = Line::create($this->orderId, $lineId, $productId, $linePrice, $quantity);

        $this->recordEvent(new LineAdded($this->orderId, $lineId, $productId));
    }

    private function updateLine(LineId $lineId, LinePrice $linePrice, Quantity $quantity): void
    {
        if (null !== $lineIndexToBeUpdated = $this->findLineIndex($lineId)) {
            $this->lines[$lineIndexToBeUpdated]->updatePrice($linePrice);
            $this->lines[$lineIndexToBeUpdated]->updateQuantity($quantity);

            $this->recordEvent(new LineUpdated($this->orderId, $lineId));
        }
    }

    public function updateLinePrice(LineId $lineId, LinePrice $linePrice): void
    {
        if (null !== $lineIndexToBeUpdated = $this->findLineIndex($lineId)) {
            $this->lines[$lineIndexToBeUpdated]->updatePrice($linePrice);

            $this->recordEvent(new LineUpdated($this->orderId, $lineId));
        }
    }

    public function updateLineQuantity(LineId $lineId, Quantity $quantity): void
    {
        if (null !== $lineIndexToBeUpdated = $this->findLineIndex($lineId)) {
            $this->lines[$lineIndexToBeUpdated]->updateQuantity($quantity);

            $this->recordEvent(new LineUpdated($this->orderId, $lineId));
        }
    }

    public function deleteLine(LineId $lineId): void
    {
        if (null !== $lineIndexToBeDeleted = $this->findLineIndex($lineId)) {
            $lineToBeDeleted = $this->lines[$lineIndexToBeDeleted];

            unset($this->lines[$lineIndexToBeDeleted]);

            $this->recordEvent(new LineDeleted($this->orderId, $lineToBeDeleted->lineId, $lineToBeDeleted->getVariantId()));
        }
    }

    private function findLineIndex(LineId $lineId): ?int
    {
        foreach ($this->lines as $index => $line) {
            if ($lineId->asInt() === $line->lineId->asInt()) {
                return $index;
            }
        }

        return null;
    }

    public function addDiscount(Discount $discount): void
    {
        // TODO:: assert order id matches
        // TODO: assert discount isnt already added... (cf. addShipping)

        if (!in_array($discount, $this->discounts)) {
            $this->discounts[] = $discount;
        }
    }

    public function deleteDiscount(DiscountId $discountId): void
    {
        /** @var Discount $existingDiscount */
        foreach($this->discounts as $indexToBeDeleted => $existingDiscount) {
            if($existingDiscount->discountId->equals($discountId)) {
                unset($this->discounts[$indexToBeDeleted]);
            }
        }
    }

    public function addShipping(Shipping $shipping): void
    {
        if (null !== $this->findShippingIndex($shipping->shippingId)) {
            throw new ShippingAlreadyOnOrder(
                'Cannot add shipping because order ['.$this->orderId->get().'] already has shipping ['.$shipping->shippingId->get().']'
            );
        }

        $this->shippings[] = $shipping;

        $this->recordEvent(new ShippingAdded($this->orderId, $shipping->shippingId));
    }

    public function updateShipping(Shipping $shipping): void
    {
        if (null === $shippingIndex = $this->findShippingIndex($shipping->shippingId)) {
            throw new CouldNotFindShippingOnOrder(
                'Cannot update shipping because order ['.$this->orderId->get().'] has no shipping by id ['.$shipping->shippingId->get().']'
            );
        }

        $this->shippings[$shippingIndex] = $shipping;

        $this->recordEvent(new ShippingUpdated($this->orderId, $shipping->shippingId));
    }

    public function deleteShipping(ShippingId $shippingId): void
    {
        if (null !== $shippingIndex = $this->findShippingIndex($shippingId)) {
            unset($this->shippings[$shippingIndex]);

            $this->recordEvent(new ShippingDeleted($this->orderId, $shippingId));
        }
    }

    private function findShippingIndex(ShippingId $shippingId): ?int
    {
        foreach ($this->shippings as $index => $shipping) {
            if ($shippingId->equals($shipping->shippingId)) {
                return $index;
            }
        }

        return null;
    }

    public function getMappedData(): array
    {
        return [
            'order_id' => $this->orderId->get(),
            'data'     => $this->data,
        ];
    }

    public function getChildEntities(): array
    {
        return [
            Line::class            => array_map(fn($line) => $line->getMappedData(), $this->lines),
            Discount::class        => array_map(fn($discount) => $discount->getMappedData(), $this->discounts),
            Shipping::class        => array_map(fn($shipping) => $shipping->getMappedData(), $this->shippings),
            ShippingAddress::class => $this->shippingAddress?->toArray(),
            BillingAddress::class  => $this->billingAddress?->toArray(),
            Payment::class         => $this->payment,
            Shopper::class         => $this->shopper,
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $order = new static();

        $order->orderId = OrderId::fromString($state['order_id']);

        $order->lines = array_map(fn($lineState) => Line::fromMappedData($lineState, $state), $childEntities[Line::class]);
        $order->discounts = array_map(fn($discountState) => Discount::fromMappedData($discountState, $state), $childEntities[Discount::class]);
        $order->shippings = array_map(fn($shippingState) => Shipping::fromMappedData($shippingState, $state), $childEntities[Shipping::class]);
        $order->shippingAddress = ShippingAddress::fromArray($childEntities[ShippingAddress::class]);
        $order->billingAddress = BillingAddress::fromArray($childEntities[BillingAddress::class]);
        $order->payment = $childEntities[Payment::class] ? Payment::fromMappedData($childEntities[Payment::class], $state) : null;
        $order->shopper = $childEntities[Shopper::class] ? Shopper::fromMappedData($childEntities[Shopper::class], $state) : null;

        $order->data = $state['data'];

        return $order;
    }
}
