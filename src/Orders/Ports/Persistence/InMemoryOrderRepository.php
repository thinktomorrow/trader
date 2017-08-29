<?php

namespace Thinktomorrow\Trader\Orders\Ports\Persistence;

use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Orders\Domain\OrderReference;
use Thinktomorrow\Trader\Orders\Domain\OrderRepository;

class InMemoryOrderRepository implements OrderRepository
{
    private static $collection = [];

    public function find(OrderId $orderId): Order
    {
        if (isset(self::$collection[(string) $orderId])) {
            return self::$collection[(string) $orderId];
        }

        throw new \RuntimeException('No order found by id ['.$orderId->get().']');
    }

    public function findOrCreate(OrderId $orderId): Order
    {
        if (isset(self::$collection[(string) $orderId])) {
            return self::$collection[(string) $orderId];
        }

        $order = new Order($this->nextIdentity());
        $this->add($order);

        return $order;
    }

    public function add(Order $order)
    {
        self::$collection[(string) $order->id()] = $order;
    }

    public function remove(OrderId $orderId)
    {
        unset(self::$collection[(string) $orderId->get()]);
    }

    public function getValues(OrderId $orderId): array
    {
        if (!isset(self::$collection[(string) $orderId])) {
            throw new \RuntimeException('Order not found by id ['.$orderId->get().']');
        }

        $order = self::$collection[(string) $orderId];

        return [
            'total'          => $order->total(),
            'subtotal'       => $order->subtotal(),
            'discount_total' => $order->discountTotal(),
            'payment_total'  => $order->paymentTotal(),
            'shipment_total' => $order->shippingTotal(),
            'tax'            => $order->tax(),
            'tax_rates'      => $order->taxRates(),
            'reference'      => $order->id()->get(), // This should be something business unique; not the id.
            'confirmed_at'   => new \DateTime('@'.strtotime('-1day')), // TODO datestamps of states should be held elsewhere no?
            'state'          => $order->state(),
        ];
    }

    public function getItemValues(OrderId $orderId): array
    {
        $order = self::$collection[(string) $orderId];

        $items = [];

        foreach ($order->items() as $item) {
            $items[] = [
                'name'          => $item->name(),
                'sku'           => '392939', // TODO
                'stock'         => 1, // TODO
                'stock_warning' => false, // TODO feature for later on
                'saleprice'     => $item->salePrice(),
                'quantity'      => $item->quantity(),
                'total'         => $item->total(),
                'taxRate'       => $item->taxRate(),
            ];
        }

        return $items;
    }

    public function getAppliedDiscounts(OrderId $orderId): array
    {
        $order = self::$collection[(string) $orderId];

        $appliedDiscounts = [];

        foreach ($order->discounts() as $discount) {
            $appliedDiscounts[] = [
                'id'          => $discount->id(),
                'type'        => $discount->type(),
                'amount'      => $discount->amount(),
                'description' => $discount->description(),
            ];
        }

        return $appliedDiscounts;
    }

    public function nextIdentity() : OrderId
    {
        return OrderId::fromString((string) Uuid::uuid4());
    }

    public function nextReference(): OrderReference
    {
        return OrderReference::fromString((string) Uuid::uuid4());
    }
}
