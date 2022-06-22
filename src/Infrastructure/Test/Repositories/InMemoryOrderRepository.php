<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\ShopperId;

final class InMemoryOrderRepository implements OrderRepository
{
    /** @var Order[] */
    public static array $orders = [];

    private string $nextReference = 'xxx-123';
    private string $nextShippingReference = 'shipping-123';
    private string $nextPaymentReference = 'payment-123';
    private string $nextShopperReference = 'shopper-123';

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

    public function delete(OrderId $orderId): void
    {
        if (! isset(static::$orders[$orderId->get()])) {
            throw new CouldNotFindOrder('No order found by id ' . $orderId);
        }

        unset(static::$orders[$orderId->get()]);
    }

    public function nextReference(): OrderId
    {
        return OrderId::fromString($this->nextReference);
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

    public static function clear()
    {
        static::$orders = [];
    }
}
