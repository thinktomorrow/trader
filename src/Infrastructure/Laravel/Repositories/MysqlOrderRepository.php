<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Domain\Common\Address\AddressType;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\OrderAlreadyInMerchantHands;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Shopper;
use Thinktomorrow\Trader\Domain\Model\Order\ShopperId;

final class MysqlOrderRepository implements OrderRepository
{
    private static $orderTable = 'trader_orders';
    private static $orderLinesTable = 'trader_order_lines';
    private static $orderDiscountsTable = 'trader_order_discounts';
    private static $orderShippingTable = 'trader_order_shipping';
    private static $orderPaymentTable = 'trader_order_payment';
    private static $orderAddressTable = 'trader_order_addresses';
    private static $orderShopperTable = 'trader_order_shoppers';

    public function save(Order $order): void
    {
        $state = $order->getMappedData();

        if (! $this->exists($order->orderId)) {
            DB::table(static::$orderTable)->insert($state);
        } else {
            DB::table(static::$orderTable)->where('order_id', $order->orderId->get())->update($state);
        }

        $this->upsertLines($order);
        $this->upsertDiscounts($order);
        $this->upsertShippings($order);
        $this->upsertPayment($order);
        $this->upsertAddresses($order);
        $this->upsertShopper($order);
    }

    private function upsertLines(Order $order): void
    {
        $lineIds = array_map(fn ($lineState) => $lineState['line_id'], $order->getChildEntities()[Line::class]);

        DB::table(static::$orderLinesTable)
            ->where('order_id', $order->orderId->get())
            ->whereNotIn('line_id', $lineIds)
            ->delete();

        // TODO:delete line discounts...

        foreach ($order->getChildEntities()[Line::class] as $lineState) {
            DB::table(static::$orderLinesTable)
                ->updateOrInsert([
                    'order_id' => $order->orderId->get(),
                    'line_id' => $lineState['line_id'],
                ], $lineState);

            // TODO: how to save line discounts...
        }
    }

    private function upsertDiscounts(Order $order): void
    {
        $discountStates = $order->getChildEntities()[Discount::class];

        foreach ($order->getLines() as $line) {
            $discountStates = array_merge($discountStates, $line->getChildEntities()[Discount::class]);
        }

        foreach ($order->getShippings() as $shipping) {
            $discountStates = array_merge($discountStates, $shipping->getChildEntities()[Discount::class]);
        }

        $discountStates = array_merge($discountStates, $order->getPayment() ? $order->getPayment()->getChildEntities()[Discount::class] : []);

        DB::table(static::$orderDiscountsTable)
            ->where('order_id', $order->orderId->get())
            ->whereNotIn('discount_id', array_map(fn ($discountState) => $discountState['discount_id'], $discountStates))
            ->delete();

        foreach ($discountStates as $discountState) {
            DB::table(static::$orderDiscountsTable)
                ->updateOrInsert([
                    'order_id' => $order->orderId->get(),
                    'discount_id' => $discountState['discount_id'],
                    'discountable_type' => $discountState['discountable_type'],
                    'discountable_id' => $discountState['discountable_id'],
                ], $discountState);
        }
    }

    private function upsertShippings(Order $order): void
    {
        $shippingIds = array_map(fn ($shippingState) => $shippingState['shipping_id'], $order->getChildEntities()[Shipping::class]);

        DB::table(static::$orderShippingTable)
            ->where('order_id', $order->orderId->get())
            ->whereNotIn('shipping_id', $shippingIds)
            ->delete();

        foreach ($order->getChildEntities()[Shipping::class] as $shippingState) {
            DB::table(static::$orderShippingTable)
                ->updateOrInsert([
                    'order_id' => $order->orderId->get(),
                    'shipping_id' => $shippingState['shipping_id'],
                ], $shippingState);

            // TODO: how to save shipping discounts...
        }
    }

    private function upsertPayment(Order $order): void
    {
        $paymentState = $order->getChildEntities()[Payment::class];

        if (is_null($paymentState)) {
            DB::table(static::$orderPaymentTable)->where('order_id', $order->orderId->get())->delete();

            return;
        }

        DB::table(static::$orderPaymentTable)
            ->updateOrInsert([
                'order_id' => $order->orderId->get(),
                'payment_id' => $paymentState['payment_id'],
            ], $paymentState);
    }

    private function upsertAddresses(Order $order): void
    {
        if ($shippingAddressState = $order->getChildEntities()[ShippingAddress::class]) {
            DB::table(static::$orderAddressTable)
                ->updateOrInsert([
                    'order_id' => $order->orderId->get(),
                    'type' => AddressType::shipping->value,
                ], $shippingAddressState);
        } else {
            DB::table(static::$orderAddressTable)
                ->where('order_id', $order->orderId->get())
                ->where('type', AddressType::shipping->value)
                ->delete();
        }

        if ($billingAddressState = $order->getChildEntities()[BillingAddress::class]) {
            DB::table(static::$orderAddressTable)
                ->updateOrInsert([
                    'order_id' => $order->orderId->get(),
                    'type' => AddressType::billing->value,
                ], $billingAddressState);
        } else {
            DB::table(static::$orderAddressTable)
                ->where('order_id', $order->orderId->get())
                ->where('type', AddressType::billing->value)
                ->delete();
        }
    }

    private function upsertShopper(Order $order): void
    {
        $shopperState = $order->getChildEntities()[Shopper::class];

        if (is_null($shopperState)) {
            DB::table(static::$orderShopperTable)->where('order_id', $order->orderId->get())->delete();

            return;
        }

        DB::table(static::$orderShopperTable)
            ->updateOrInsert([
                'order_id' => $order->orderId->get(),
                'shopper_id' => $shopperState['shopper_id'],
            ], $shopperState);
    }

    private function exists(OrderId $orderId): bool
    {
        return DB::table(static::$orderTable)->where('order_id', $orderId->get())->exists();
    }

    private function existsReference(OrderReference $orderReference): bool
    {
        return DB::table(static::$orderTable)->where('order_ref', $orderReference->get())->exists();
    }

    public function find(OrderId $orderId): Order
    {
        $orderState = DB::table(static::$orderTable)
            ->where(static::$orderTable . '.order_id', $orderId->get())
            ->first();

        if (! $orderState) {
            throw new CouldNotFindOrder('No order found by id [' . $orderId->get() . ']');
        }

        $allDiscountStates = DB::table(static::$orderDiscountsTable)
            ->where(static::$orderDiscountsTable . '.order_id', $orderId->get())
            ->get()
            ->map(fn ($item) => (array)$item)
            ->map(fn ($item) => array_merge($item, ['includes_vat' => (bool)$item['includes_vat']]));

        $lineStates = DB::table(static::$orderLinesTable)
            ->where(static::$orderLinesTable . '.order_id', $orderId->get())
            ->get()
            ->map(fn ($item) => (array)$item)
            ->map(fn ($item) => array_merge($item, [
                'includes_vat' => (bool)$item['includes_vat'],
                Discount::class => $allDiscountStates->filter(fn ($discountState) => $discountState['discountable_type'] == DiscountableType::line->value && $discountState['discountable_id'] == $item['line_id'])->values()->toArray(),
            ]))
            ->toArray();

        $shippingStates = DB::table(static::$orderShippingTable)
            ->where(static::$orderShippingTable . '.order_id', $orderId->get())
            ->get()
            ->map(fn ($item) => (array)$item)
            ->map(fn ($item) => array_merge($item, [
                'includes_vat' => (bool)$item['includes_vat'],
                Discount::class => $allDiscountStates->filter(fn ($discountState) => $discountState['discountable_type'] == DiscountableType::shipping->value && $discountState['discountable_id'] == $item['shipping_id'])->values()->toArray(),
            ]))
            ->toArray();

        $paymentState = DB::table(static::$orderPaymentTable)
            ->where(static::$orderPaymentTable . '.order_id', $orderId->get())
            ->first();

        if (! is_null($paymentState)) {
            $paymentState = (array)$paymentState;
            $paymentState = array_merge($paymentState, [
                'includes_vat' => (bool)$paymentState['includes_vat'],
                Discount::class => $allDiscountStates->filter(fn ($discountState) => $discountState['discountable_type'] == DiscountableType::payment->value && $discountState['discountable_id'] == $paymentState['payment_id'])->values()->toArray(),
            ]);
        }

        $addressStates = DB::table(static::$orderAddressTable)
            ->where(static::$orderAddressTable . '.order_id', $orderId->get())
            ->get();

        $shippingAddressState = $addressStates->first(fn ($address) => $address->type == AddressType::shipping->value);
        $billingAddressState = $addressStates->first(fn ($address) => $address->type == AddressType::billing->value);

        $shopperState = DB::table(static::$orderShopperTable)
            ->where(static::$orderShopperTable . '.order_id', $orderId->get())
            ->first();

        if (! is_null($shopperState)) {
            $shopperState = (array)$shopperState;
            $shopperState = array_merge($shopperState, [
                'register_after_checkout' => (bool)$shopperState['register_after_checkout'],
                'is_business' => (bool)$shopperState['is_business'],
            ]);
        }

        $childEntities = [
            Line::class => $lineStates,
            Discount::class => $allDiscountStates->filter(fn ($discountState) => $discountState['discountable_type'] == DiscountableType::order->value && $discountState['discountable_id'] == $orderState->order_id)->values()->toArray(),
            Shipping::class => $shippingStates,
            Payment::class => $paymentState,
            Shopper::class => $shopperState,
            ShippingAddress::class => $shippingAddressState ? (array)$shippingAddressState : null,
            BillingAddress::class => $billingAddressState ? (array)$billingAddressState : null,
        ];

        return Order::fromMappedData((array)$orderState, $childEntities);
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
        DB::table(static::$orderTable)->where('order_id', $orderId->get())->delete();
    }

    public function nextReference(): OrderId
    {
        return OrderId::fromString((string)Uuid::uuid4());
    }

    public function nextExternalReference(): OrderReference
    {
        $orderReference = null;

        while (! $orderReference || $this->existsReference($orderReference)) {
            $orderReference = OrderReference::fromString(date('ymdHis').'-'. str_pad((string) mt_rand(1, 999), 3, "0"));
        }

        return $orderReference;
    }

    public function nextShippingReference(): ShippingId
    {
        return ShippingId::fromString((string)Uuid::uuid4());
    }

    public function nextPaymentReference(): PaymentId
    {
        return PaymentId::fromString((string)Uuid::uuid4());
    }

    public function nextShopperReference(): ShopperId
    {
        return ShopperId::fromString((string)Uuid::uuid4());
    }

    public function nextDiscountReference(): DiscountId
    {
        return DiscountId::fromString((string)Uuid::uuid4());
    }
}
