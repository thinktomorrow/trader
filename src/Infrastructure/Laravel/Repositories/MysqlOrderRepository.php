<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Shopper;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;

final class MysqlOrderRepository implements OrderRepository
{
    private static $orderTable = 'trader_orders';
    private static $orderLinesTable = 'trader_order_lines';
    private static $orderDiscountsTable = 'trader_order_discounts';
    private static $orderShippingTable = 'trader_order_shipping';
    private static $orderPaymentTable = 'trader_order_payment';
    private static $orderShopperTable = 'trader_order_shoppers';

    public function save(Order $order): void
    {
        $state = $order->getMappedData();

        $state = array_merge($state, [
            'shipping_address' => json_encode($order->getChildEntities()[ShippingAddress::class]),
            'billing_address'  => json_encode($order->getChildEntities()[BillingAddress::class]),
        ]);

        if (!$this->exists($order->orderId)) {
            DB::table(static::$orderTable)->insert($state);
        } else {
            DB::table(static::$orderTable)->where('order_id', $order->orderId)->update($state);
        }

        $this->upsertLines($order);
        $this->upsertDiscounts($order);
        $this->upsertShippings($order);
        $this->upsertPayment($order);
        $this->upsertShopper($order);
    }

    private function upsertLines(Order $order): void
    {
        $lineIds = array_map(fn($lineState) => $lineState['line_id'], $order->getChildEntities()[Line::class]);

        DB::table(static::$orderLinesTable)
            ->where('order_id', $order->orderId)
            ->whereNotIn('line_id', $lineIds)
            ->delete();

        foreach ($order->getChildEntities()[Line::class] as $lineState) {
            DB::table(static::$orderLinesTable)
                ->updateOrInsert([
                    'order_id' => $order->orderId->get(),
                    'line_id'  => $lineState['line_id'],
                ], $lineState);
        }
    }

    private function upsertDiscounts(Order $order): void
    {
        $discountIds = array_map(fn($discountState) => $discountState['discount_id'], $order->getChildEntities()[Discount::class]);

        DB::table(static::$orderDiscountsTable)
            ->where('order_id', $order->orderId)
            ->whereNotIn('discount_id', $discountIds)
            ->delete();

        foreach ($order->getChildEntities()[Discount::class] as $discountState) {
            DB::table(static::$orderDiscountsTable)
                ->updateOrInsert([
                    'order_id'    => $order->orderId->get(),
                    'discount_id' => $discountState['discount_id'],
                ], $discountState);
        }
    }

    private function upsertShippings(Order $order): void
    {
        $shippingIds = array_map(fn($shippingState) => $shippingState['shipping_id'], $order->getChildEntities()[Shipping::class]);

        DB::table(static::$orderShippingTable)
            ->where('order_id', $order->orderId)
            ->whereNotIn('shipping_id', $shippingIds)
            ->delete();

        foreach ($order->getChildEntities()[Shipping::class] as $shippingState) {
            DB::table(static::$orderShippingTable)
                ->updateOrInsert([
                    'order_id'    => $order->orderId->get(),
                    'shipping_id' => $shippingState['shipping_id'],
                ], $shippingState);
        }
    }

    private function upsertPayment(Order $order): void
    {
        $paymentState = $order->getChildEntities()[Payment::class];

        if(is_null($paymentState)) {
            DB::table(static::$orderPaymentTable)->where('order_id', $order->orderId)->delete();
            return;
        }

        DB::table(static::$orderPaymentTable)
            ->updateOrInsert([
                'order_id'   => $order->orderId->get(),
                'payment_id' => $paymentState['payment_id'],
            ], $paymentState);
    }

    private function upsertShopper(Order $order): void
    {
        $shopperState = $order->getChildEntities()[Shopper::class];

        if(is_null($shopperState)) {
            DB::table(static::$orderShopperTable)->where('order_id', $order->orderId)->delete();
            return;
        }

        DB::table(static::$orderShopperTable)
            ->updateOrInsert([
                'order_id'   => $order->orderId->get(),
                'shopper_id' => $shopperState['shopper_id'],
            ], $shopperState);
    }

    private function exists(OrderId $orderId): bool
    {
        return DB::table(static::$orderTable)->where('order_id', $orderId->get())->exists();
    }

    public function find(OrderId $orderId): Order
    {
        $orderState = DB::table(static::$orderTable)
            ->where(static::$orderTable . '.order_id', $orderId->get())
            ->first();

        if (!$orderState) {
            throw new CouldNotFindOrder('No order found by id [' . $orderId->get() . ']');
        }

        $lineStates = DB::table(static::$orderLinesTable)
            ->where(static::$orderLinesTable . '.order_id', $orderId->get())
            ->get()
            ->map(fn($item) => (array) $item)
            ->map(fn($item) => array_merge($item, ['includes_vat' => (bool) $item['includes_vat']]))
            ->toArray();

        $discountStates = DB::table(static::$orderDiscountsTable)
            ->where(static::$orderDiscountsTable . '.order_id', $orderId->get())
            ->get()
            ->map(fn($item) => (array) $item)
            ->map(fn($item) => array_merge($item, ['includes_vat' => (bool) $item['includes_vat']]))
            ->toArray();

        $shippingStates = DB::table(static::$orderShippingTable)
            ->where(static::$orderShippingTable . '.order_id', $orderId->get())
            ->get()
            ->map(fn($item) => (array) $item)
            ->map(fn($item) => array_merge($item, ['includes_vat' => (bool) $item['includes_vat']]))
            ->toArray();

        $paymentState = DB::table(static::$orderPaymentTable)
            ->where(static::$orderPaymentTable . '.order_id', $orderId->get())
            ->first();

        if(! is_null($paymentState)) {
            $paymentState = (array) $paymentState;
            $paymentState = array_merge($paymentState, ['includes_vat' => (bool) $paymentState['includes_vat']]);
        }

        $shopperState = DB::table(static::$orderShopperTable)
            ->where(static::$orderShopperTable . '.order_id', $orderId->get())
            ->first();

        if(! is_null($shopperState)) {
            $shopperState = (array) $shopperState;
            $shopperState = array_merge($shopperState, ['register_after_checkout' => (bool) $shopperState['register_after_checkout']]);
        }

        $childEntities = [
            Line::class            => $lineStates,
            Discount::class        => $discountStates,
            Shipping::class        => $shippingStates,
            Payment::class         => $paymentState,
            Shopper::class         => $shopperState,
            ShippingAddress::class => $orderState->shipping_address ? json_decode($orderState->shipping_address, TRUE) : null,
            BillingAddress::class  => $orderState->billing_address ? json_decode($orderState->billing_address, TRUE) : null,
        ];

        return Order::fromMappedData((array)$orderState, $childEntities);
    }

    public function delete(OrderId $orderId): void
    {
        DB::table(static::$orderTable)->where('order_id', $orderId->get())->delete();
    }

    public function nextReference(): OrderId
    {
        return OrderId::fromString((string)Uuid::uuid4());
    }

    public function nextShippingReference(): ShippingId
    {
        return ShippingId::fromString((string)Uuid::uuid4());
    }
}