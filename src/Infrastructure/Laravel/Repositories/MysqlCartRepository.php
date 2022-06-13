<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\Shopper;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Application\Cart\Read\CartRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindOrder;

final class MysqlCartRepository implements CartRepository
{
    private static $orderTable = 'trader_orders';
    private static $orderLinesTable = 'trader_order_lines';
    private static $orderDiscountsTable = 'trader_order_discounts';
    private static $orderShippingTable = 'trader_order_shipping';
    private static $orderPaymentTable = 'trader_order_payment';
    private static $orderShopperTable = 'trader_order_shoppers';

    private function exists(OrderId $orderId): bool
    {
        return DB::table(static::$orderTable)->where('order_id', $orderId->get())->exists();
    }

    public function findCart(OrderId $orderId): Cart
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
}
