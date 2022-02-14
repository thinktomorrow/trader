<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Entity;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\LineNumber;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Model\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineAdded;
use Thinktomorrow\Trader\Domain\Model\Payment\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineDeleted;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingAddress;
use Thinktomorrow\Trader\Domain\Common\Entity\RecordsChangelog;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderCreated;

final class Order implements Aggregate
{
    use RecordsEvents;
    use RecordsChangelog;

    public readonly OrderId $orderId;

    private array $lines = [];
    private CustomerId $customerId;

    private ?Shipping $shipping = null;
    private ?PaymentId $paymentId = null;
    private ?ShippingAddress $shippingAddress = null;
    private ?BillingAddress $billingAddress = null;
    private array $discountIds = [];

    // Keep track of changes, updates that can be used for logging or checkout info.
    // As soon as order is confirmed, the version is fixated.
    private string $version;
    private array $versionNotes;

    private function __construct(){}

    public static function create(OrderId $orderId, CustomerId $customerId)
    {
        $order = new static();

        $order->orderId = $orderId;
        $order->customerId = $customerId;

        $order->recordEvent(new OrderCreated($order->orderId));

        return $order;
    }

    public function updateCustomer(CustomerId $customerId): void
    {
        $this->update('customerId', $customerId);
    }

    public function getShippingId(): ?ShippingId
    {
        return $this->shipping?->getShippingId();
    }

    public function updateShipping(ShippingId $shippingId, ShippingState $shippingState, ShippingTotal $shippingTotal, array $data): void
    {
        if($this->shipping) {
            $this->shipping->update($shippingId, $shippingState, $shippingTotal, $data);
        } else {
            $this->shipping = Shipping::create(
                $this->orderId, $shippingId, $shippingState, $shippingTotal, $data
            );
        }

        $this->recordEvent(new OrderUpdated($this->orderId));
    }

    public function updatePayment(PaymentId $paymentId): void
    {
        $this->update('paymentId', $paymentId);
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

    public function addOrUpdateLine(LineNumber $lineNumber, ProductId $productId, Quantity $quantity): void
    {
        if(null !== $this->findLineIndex($lineNumber)) {
            $this->updateLine($lineNumber, $productId, $quantity);
            return;
        }

        $this->addLine($lineNumber, $productId, $quantity);
    }

    private function addLine(LineNumber $lineNumber, ProductId $productId, Quantity $quantity): void
    {
        $this->lines[] = Line::create($this->orderId, $lineNumber, $productId, $quantity);

        $this->recordEvent(new LineAdded($this->orderId, $lineNumber, $productId, $quantity));
    }

    private function updateLine(LineNumber $lineNumber, ProductId $productId, Quantity $quantity): void
    {
        if(null !== $lineIndexToBeUpdated = $this->findLineIndex($lineNumber)) {
            $this->lines[$lineIndexToBeUpdated]->update($productId, $quantity);
        }

        $this->recordEvent(new LineUpdated($this->orderId, $lineNumber, $productId, $quantity));
    }

    public function deleteLine(LineNumber $lineNumber): void
    {
        if(null !== $lineIndexToBeDeleted = $this->findLineIndex($lineNumber)) {
            $lineToBeDeleted = $this->lines[$lineIndexToBeDeleted];

            unset($this->lines[$lineIndexToBeDeleted]);

            $this->recordEvent(new LineDeleted($this->orderId, $lineToBeDeleted->lineNumber, $lineToBeDeleted->getProductId()));
        }
    }

    public function addDiscount(DiscountId $discountId): void
    {
        if(! in_array($discountId, $this->discountIds)) {
            $this->discountIds[] = $discountId;
        }
    }

    public function deleteDiscount(DiscountId $discountId): void
    {
        if(false !== ($indexToBeDeleted = array_search($discountId, $this->discountIds))) {
            unset($this->discountIds[$indexToBeDeleted]);
        }
    }

    public function getMappedData(): array
    {
        return [
            'order_id' => $this->orderId->get(),
            'customer_id' => $this->customerId->get(),
            'shipping_id' => $this->shippingId?->get(),
            'payment_id' => $this->paymentId?->get(),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            Line::class => $this->lines,
            DiscountId::class => $this->discountIds,
            ShippingAddress::class => $this->shippingAddress,
            BillingAddress::class => $this->billingAddress,
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $order = new static();

        $order->orderId = OrderId::fromString($state['order_id']);
        $order->customerId = CustomerId::fromString($state['customer_id']);
        $order->shippingId = ShippingId::fromString($state['shipping_id']);
        $order->paymentId = PaymentId::fromString($state['payment_id']);

        return $order;
    }

    private function findLineIndex(LineNumber $lineNumber): ?int
    {
        foreach($this->lines as $index => $line) {
            if($lineNumber->asInt() === $line->lineNumber->asInt()) {
                return $index;
            }
        }

        return null;
    }
}
