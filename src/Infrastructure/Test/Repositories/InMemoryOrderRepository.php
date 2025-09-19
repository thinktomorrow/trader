<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\OrderAlreadyInMerchantHands;
use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceReference;
use Thinktomorrow\Trader\Domain\Model\Order\Invoice\InvoiceRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Personalisations\LinePersonalisationId;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderEvent\OrderEventId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\ShopperId;

final class InMemoryOrderRepository implements OrderRepository, InvoiceRepository, InMemoryRepository
{
    /** @var Order[] */
    public static array $orders = [];

    private string $nextReference = 'xxx-123';
    private string $nextShippingReference = 'shipping-123';
    private string $nextPaymentReference = 'payment-123';
    private string $nextShopperReference = 'shopper-123';
    private string $nextDiscountReference = 'discount-123';
    private string $nextLineReference = 'line';

    public function save(Order $order): void
    {
        static::$orders[$order->orderId->get()] = $order;
    }

    public function find(OrderId $orderId): Order
    {
        if (! isset(static::$orders[$orderId->get()])) {
            throw new CouldNotFindOrder('No order found by id ' . $orderId);
        }

        return static::$orders[$orderId->get()];
    }

    public function findForCart(OrderId $orderId): Order
    {
        $order = $this->find($orderId);

        if (! $order->inCustomerHands()) {
            throw new OrderAlreadyInMerchantHands('Cannot fetch order for cart. Order is no longer in customer hands and has already the following state: ' . $order->getOrderState()->value);
        }

        return $order;
    }

    public function delete(OrderId $orderId): void
    {
        if (! isset(static::$orders[$orderId->get()])) {
            throw new CouldNotFindOrder('No order found by id ' . $orderId);
        }

        unset(static::$orders[$orderId->get()]);
    }

    public function findIdByReference(OrderReference $orderReference): OrderId
    {
        foreach (static::$orders as $order) {
            if ($order->orderReference->equals($orderReference)) {
                return $order->orderId;
            }
        }
    }

    public function nextReference(): OrderId
    {
        return OrderId::fromString($this->nextReference);
    }

    public function nextExternalReference(): OrderReference
    {
        return OrderReference::fromString(
            date('ymdHis') . str_pad((string)mt_rand(1, 999), 3, "0")
        );
    }

    public function nextInvoiceReference(): InvoiceReference
    {
        return InvoiceReference::fromString('invoice- ' . $this->nextReference);
    }

    public function lastInvoiceReference(): ?InvoiceReference
    {
        foreach (static::$orders as $order) {
            return $order->getInvoiceReference();
        }

        return null;
    }

    public function nextShippingReference(): ShippingId
    {
        return ShippingId::fromString($this->nextShippingReference);
    }

    public function nextPaymentReference(): PaymentId
    {
        return PaymentId::fromString($this->nextPaymentReference);
    }

    public function nextShopperReference(): ShopperId
    {
        return ShopperId::fromString($this->nextShopperReference);
    }

    public function nextDiscountReference(): DiscountId
    {
        return DiscountId::fromString($this->nextDiscountReference . '-' . mt_rand(1, 999));
    }

    public function nextLineReference(): LineId
    {
        return LineId::fromString($this->nextLineReference . '-' . mt_rand(1, 999));
    }

    public function setNextLineReference(string $nextLineReference): void
    {
        $this->nextLineReference = $nextLineReference;
    }

    public function nextLinePersonalisationReference(): LinePersonalisationId
    {
        return LinePersonalisationId::fromString('' . mt_rand(1, 999));
    }

    public function nextLogEntryReference(): OrderEventId
    {
        return OrderEventId::fromString('entry_id_' . mt_rand(1, 999));
    }

    // For testing purposes only
    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public function setNextShippingReference(string $nextShippingReference): void
    {
        $this->nextShippingReference = $nextShippingReference;
    }

    public function setNextPaymentReference(string $nextPaymentReference): void
    {
        $this->nextPaymentReference = $nextPaymentReference;
    }

    public function setNextShopperReference(string $nextShopperReference): void
    {
        $this->nextShopperReference = $nextShopperReference;
    }

    public function setNextDiscountReference(string $nextDiscountReference): void
    {
        $this->nextDiscountReference = $nextDiscountReference;
    }

    public static function clear()
    {
        static::$orders = [];
    }
}
