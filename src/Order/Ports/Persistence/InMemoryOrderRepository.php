<?php

namespace Thinktomorrow\Trader\Order\Ports\Persistence;

use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Order\Domain\OrderId;
use Thinktomorrow\Trader\Order\Domain\OrderRepository;

class InMemoryOrderRepository implements OrderRepository
{
    private static $collection = [];

    public function find(OrderId $orderId)
    {
        if (isset(self::$collection[(string) $orderId])) {
            return self::$collection[(string) $orderId];
        }

        return null;
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
            'shipment_total' => $order->shipmentTotal(),
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
}
